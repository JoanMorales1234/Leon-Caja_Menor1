<?php

namespace App\Controllers;

use App\Core\Controller;

class LogoController extends Controller
{
    public function index()
    {
        $message = '';
        $type = 'info';

        $logoPath = 'uploads/logo.png';
        $logoExists = file_exists(ROOT_PATH . '/' . $logoPath);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['eliminar']) && $logoExists) {
                @unlink(ROOT_PATH . '/' . $logoPath);
                $message = 'Logo eliminado.';
                $type = 'success';
                $logoExists = false;
            } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed)) {
                    $message = 'Formato no permitido. Usa JPG, PNG, GIF o WEBP.';
                    $type = 'danger';
                } else {
                    $uploadDir = ROOT_PATH . '/uploads';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $tempFile = $_FILES['logo']['tmp_name'];
                    if (function_exists('getimagesize')) {
                        $imgInfo = getimagesize($tempFile);
                        if (!$imgInfo) {
                            $message = 'El archivo no es una imagen válida.';
                            $type = 'danger';
                        } elseif (function_exists('imagecreatefrompng')) {
                            $srcW = $imgInfo[0];
                            $srcH = $imgInfo[1];
                            switch ($imgInfo[2]) {
                                case IMAGETYPE_JPEG: $srcImg = imagecreatefromjpeg($tempFile); break;
                                case IMAGETYPE_PNG: $srcImg = imagecreatefrompng($tempFile); break;
                                case IMAGETYPE_GIF: $srcImg = imagecreatefromgif($tempFile); break;
                                case IMAGETYPE_WEBP: $srcImg = imagecreatefromwebp($tempFile); break;
                                default: $srcImg = null;
                            }
                            if (!$srcImg) {
                                $message = 'Error al procesar la imagen.';
                                $type = 'danger';
                            } else {
                                $maxW = 300;
                                $maxH = 100;
                                $ratio = min($maxW / $srcW, $maxH / $srcH, 1);
                                $dstW = (int)round($srcW * $ratio);
                                $dstH = (int)round($srcH * $ratio);
                                $dstImg = imagecreatetruecolor($dstW, $dstH);
                                imagealphablending($dstImg, false);
                                imagesavealpha($dstImg, true);
                                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
                                imagepng($dstImg, ROOT_PATH . '/' . $logoPath);
                                imagedestroy($srcImg);
                                imagedestroy($dstImg);
                                $message = 'Logo subido y redimensionado correctamente.';
                                $type = 'success';
                                $logoExists = true;
                            }
                        } else {
                            if (move_uploaded_file($tempFile, ROOT_PATH . '/' . $logoPath)) {
                                $message = 'Logo subido (sin redimensionar, extensión GD no disponible).';
                                $type = 'success';
                                $logoExists = true;
                            } else {
                                $message = 'Error al guardar el archivo.';
                                $type = 'danger';
                            }
                        }
                    } else {
                        if (move_uploaded_file($tempFile, ROOT_PATH . '/' . $logoPath)) {
                            $message = 'Logo subido (sin redimensionar).';
                            $type = 'success';
                            $logoExists = true;
                        } else {
                            $message = 'Error al guardar el archivo.';
                            $type = 'danger';
                        }
                    }
                }
            } else {
                $message = 'Selecciona un archivo de imagen.';
                $type = 'danger';
            }
        }

        $this->view('layout/header', ['pageTitle' => 'Configurar Logo']);
        $this->view('logo', compact('message', 'type', 'logoPath', 'logoExists'));
        $this->view('layout/footer');
    }
}
