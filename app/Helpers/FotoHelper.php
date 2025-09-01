<?php

namespace App\Helpers;

class FotoHelper
{
    /**
     * Busca y devuelve la URL pública de la foto.
     * Prioridad:
     * 1) URL ya guardada en BD.
     * 2) Archivo ya existente en:
     *    - storage/app/public/fotos_perfil
     *    - public/storage/fotos_perfil
     *    - public/fotos_perfil
     * 3) Si no existe local, intenta leer del share \\172.20.13.10\fotos_imagencarnets\<base>.(jpg|jpeg|png)
     *    y genera <base>.jpg en storage/app/public/fotos_perfil (con Intervention si está disponible; si no, GD).
     */
    public static function resolverFoto(?string $fotoPerfilDb, ?string $rutaImagenDb): ?string
    {
        // 0) Si ya viene la URL desde BD, úsala
        if (!empty($fotoPerfilDb)) {
            return trim($fotoPerfilDb);
        }
        if (empty($rutaImagenDb)) {
            return null;
        }

        // 1) Normalizar nombre base
        $rutaImagenDb = trim($rutaImagenDb);
        $base = pathinfo($rutaImagenDb, PATHINFO_FILENAME);
        if ($base === '' || $base === null) {
            $base = preg_replace('/\.(jpg|jpeg|png)$/i', '', $rutaImagenDb);
        }
        $base = self::normalizeBase((string)$base); // quita espacios raros/extra

        // 2) Buscar archivo ya existente (varias ubicaciones)
        $exts = ['jpg','jpeg','png','JPG','JPEG','PNG'];

        // a) storage/app/public/fotos_perfil  -> /storage/fotos_perfil
        $storageDir  = storage_path('app/public/fotos_perfil');
        $urlBuilderA = fn($file) => asset('storage/fotos_perfil/' . $file);
        if ($url = self::findExisting($storageDir, $base, $exts, $urlBuilderA)) {
            return $url;
        }

        // b) public/storage/fotos_perfil  (gente que coloca directo bajo public/) -> /storage/fotos_perfil
        $publicStorageDir  = public_path('storage/fotos_perfil');
        $urlBuilderB = fn($file) => asset('storage/fotos_perfil/' . $file);
        if ($url = self::findExisting($publicStorageDir, $base, $exts, $urlBuilderB)) {
            return $url;
        }

        // c) public/fotos_perfil -> /fotos_perfil
        $publicDir  = public_path('fotos_perfil');
        $urlBuilderC = fn($file) => asset('fotos_perfil/' . $file);
        if ($url = self::findExisting($publicDir, $base, $exts, $urlBuilderC)) {
            return $url;
        }

        // 3) No está local: intentar leer desde el share
        $rutaServidorBase = '\\\\172.20.13.10\\fotos_imagencarnets\\';
        $srcPath = null;
        foreach (['JPG','JPEG','PNG','jpg','jpeg','png'] as $ext) {
            $cand = $rutaServidorBase . $base . '.' . $ext;
            if (@is_readable($cand)) { $srcPath = $cand; break; }
        }
        if (!$srcPath) {
            return null;
        }

        // 4) Procesar y generar JPG en storage/app/public/fotos_perfil
        if (!is_dir($storageDir)) { @mkdir($storageDir, 0755, true); }

        $destPath = $storageDir . DIRECTORY_SEPARATOR . $base . '.jpg';
        $ok = self::makeThumbJpg($srcPath, $destPath, 200, 260);

        if ($ok && @is_file($destPath)) {
            return asset('storage/fotos_perfil/' . $base . '.jpg');
        }

        return null;
    }

    /**
     * Busca un archivo por nombre base en un directorio:
     * - Primero prueba base.ext exacto
     * - Luego escanea la carpeta (glob) y compara "normalizado" por si hay espacios o dobles extensiones
     */
    private static function findExisting(?string $dir, string $base, array $exts, callable $urlBuilder): ?string
    {
        if (!$dir) return null;

        // Intento directo
        foreach ($exts as $ext) {
            $cand = $dir . DIRECTORY_SEPARATOR . $base . '.' . $ext;
            if (@is_file($cand)) {
                return $urlBuilder(basename($cand));
            }
        }

        // Escaneo completo (maneja "1311353 .JPG", "1311353.JPG.jpg", etc.)
        $pattern = $dir . DIRECTORY_SEPARATOR . '*.{'.implode(',', $exts).'}';
        $lista = @glob($pattern, GLOB_BRACE) ?: [];
        foreach ($lista as $fullPath) {
            if (!@is_file($fullPath)) continue;
            $bn   = pathinfo($fullPath, PATHINFO_FILENAME);
            $norm = self::normalizeBase($bn);
            if ($norm === $base) {
                return $urlBuilder(basename($fullPath));
            }
        }
        return null;
    }

    /**
     * Genera un JPG redimensionado (sin upsize)  con filtros suaves.
     * Usa Intervention si está instalada; de lo contrario, GD.
     */
    private static function makeThumbJpg(string $src, string $dest, int $maxW, int $maxH): bool
    {
        // --- Rama Intervention (si está disponible) ---
        if (class_exists(\Intervention\Image\ImageManager::class)) {
            try {
                $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                $image = $manager->read($src);

                // no upsize: calculamos escala manual
                $srcW = $image->width();
                $srcH = $image->height();
                $scale = min($maxW / max(1,$srcW), $maxH / max(1,$srcH));
                if ($scale > 1) { $scale = 1; }
                $newW = max(1, (int) floor($srcW * $scale));
                $newH = max(1, (int) floor($srcH * $scale));

                $image->resize($newW, $newH)
                      ->brightness(5)
                      ->contrast(10)
                      ->sharpen(12)
                      ->toJpeg(90);

                // asegurar carpeta
                $dir = dirname($dest);
                if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
                $image->save($dest);
                return true;
            } catch (\Throwable $e) {
                // Si falla Intervention, continuamos con GD
            }
        }

        // --- Rama GD (fallback) ---
        $img = self::loadImage($src);
        if (!$img) return false;

        $srcW = imagesx($img);
        $srcH = imagesy($img);

        $scale = min($maxW / max(1,$srcW), $maxH / max(1,$srcH));
        if ($scale > 1) { $scale = 1; }
        $newW = max(1, (int) floor($srcW * $scale));
        $newH = max(1, (int) floor($srcH * $scale));

        $dst = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagealphablending($dst, true);

        imagecopyresampled($dst, $img, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        @imagefilter($dst, IMG_FILTER_BRIGHTNESS, 5);
        @imagefilter($dst, IMG_FILTER_CONTRAST, -10);
        $sharpen = [[0,-1,0],[-1,5,-1],[0,-1,0]];
        @imageconvolution($dst, $sharpen, 1, 0);

        $dir = dirname($dest);
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        $ok = @imagejpeg($dst, $dest, 90);

        imagedestroy($dst);
        imagedestroy($img);

        return (bool)$ok;
    }

    private static function loadImage(string $path)
    {
        $info = @getimagesize($path);
        if (!$info || !isset($info[2])) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg'])) return @imagecreatefromjpeg($path) ?: null;
            if ($ext === 'png') return @imagecreatefrompng($path) ?: null;
            return null;
        }
        switch ($info[2]) {
            case IMAGETYPE_JPEG: return @imagecreatefromjpeg($path) ?: null;
            case IMAGETYPE_PNG:  return @imagecreatefrompng($path) ?: null;
            default:             return null;
        }
    }

    /** Normaliza base quitando espacios/raros y colapsando múltiples espacios. */
    private static function normalizeBase(string $s): string
    {
        $s = preg_replace('/[^\S\r\n]+/u', ' ', $s ?? '');
        $s = trim($s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return $s;
    }
}
