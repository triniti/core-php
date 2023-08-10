<?php
declare(strict_types=1);

namespace Triniti\Dam\Util;

use GuzzleHttp\Psr7\MimeType;

class MimeTypeUtil
{
    private static ?string $mimeTypeToResolve = null;

    public static function mimeTypeFromFilename(string $filename): ?string
    {
        return self::mimeTypeFromExtension(pathinfo($filename, PATHINFO_EXTENSION));
    }

    public static function mimeTypeFromExtension(string $extension): ?string
    {
        return match ($extension) {
            'mxf' => 'application/mxf',
            'srt' => 'text/srt',
            'vtt' => 'text/vtt',
            default => MimeType::fromExtension($extension),
        };
    }

    public static function assetTypeFromMimeType(?string $mimeType = null): string
    {
        if (null === $mimeType) {
            return 'unknown';
        }

        self::$mimeTypeToResolve = $mimeType;
        switch (true) {
            case self::check('application/x-7z-compressed');
            case self::check('application/x-bzip');
            case self::check('application/x-bzip2');
            case self::check('application/x-gtar');
            case self::check('application/x-gzip');
            case self::check('application/x-rar-compressed');
            case self::check('application/x-stuffit');
            case self::check('application/x-stuffitx');
            case self::check('application/x-tar');
            case self::check('application/zip');
                return 'archive';

            case self::check('application/ogg');
            case self::check('audio/flac');
            case self::check('audio/mp3'); // Not in data definition list (https://stackoverflow.com/questions/10688588/which-mime-type-should-i-use-for-mp3)
            case self::check('audio/mp4');
            case self::check('audio/mpeg');
            case self::check('audio/ogg');
            case self::check('audio/x-aac');
            case self::check('audio/x-aiff');
            case self::check('audio/x-ms-wma');
            case self::check('audio/x-wav');
                return 'audio';

            case self::check('application/atom+xml');
            case self::check('application/java-archive');
            case self::check('application/javascript');
            case self::check('application/json');
            case self::check('application/rss+xml');
            case self::check('application/wsdl+xml');
            case self::check('application/xhtml+xml');
            case self::check('application/xml');
            case self::check('application/xml-dtd');
            case self::check('text/css');
            case self::check('text/html');
            case self::check('text/javascript');
            case self::check('text/yaml');
                return 'code';

            case self::check('application/msword');
            case self::check('application/pdf');
            case self::check('application/postscript');
            case self::check('application/rtf');
            case self::check('application/vnd.ms-excel');
            case self::check('application/vnd.ms-powerpoint');
            case self::check('application/vnd.ms-word');
            case self::check('application/vnd.openxmlformats-officedocument');
            case self::check('application/vnd.visio');
            case self::check('application/vnd.visio2013');
            case self::check('application/x-mswrite');
            case self::check('image/vnd.adobe.photoshop');
            case self::check('text/csv');
            case self::check('text/plain');
            case self::check('text/richtext');
            case self::check('text/srt');
            case self::check('text/tab-separated-values');
            case self::check('text/vtt');
                return 'document';

            case self::check('image/bmp');
            case self::check('image/gif');
            case self::check('image/jpeg');
            case self::check('image/pjpeg');
            case self::check('image/png');
            case self::check('image/svg+xml');
            case self::check('image/tiff');
            case self::check('image/webp');
            case self::check('image/x-icon');
            case self::check('image/x-pict');
            case self::check('image/x-portable-anymap');
            case self::check('image/x-portable-bitmap');
            case self::check('image/x-portable-graymap');
            case self::check('image/x-portable-pixmap');
            case self::check('image/x-rgb');
            case self::check('image/x-xbitmap');
            case self::check('image/x-xpixmap');
            case self::check('image/x-xwindowdump');
                return 'image';

            case self::check('application/mp21');
            case self::check('application/mp4');
            case self::check('application/mxf');
            case self::check('application/x-director');
            case self::check('application/x-dvi');
            case self::check('application/x-shockwave-flash');
            case self::check('application/x-silverlight-app');
            case self::check('video/h261');
            case self::check('video/h263');
            case self::check('video/h264');
            case self::check('video/jpeg');
            case self::check('video/jpm');
            case self::check('video/mj2');
            case self::check('video/mp4');
            case self::check('video/mpeg');
            case self::check('video/ogg');
            case self::check('video/quicktime');
            case self::check('video/webm');
            case self::check('video/x-f4v');
            case self::check('video/x-flv');
            case self::check('video/x-m4v');
            case self::check('video/x-ms');
            case self::check('video/x-msvideo');
            case self::check('video/x-sgi-movie');
                return 'video';

            default:
                return 'unknown';
        }
    }

    /**
     * Checks the mimetype in self::$mimeTypeToResolve against the string
     * passed into this method and returns a bool to indicate if it's
     * either an exact match or contained in the string.
     *
     * @param string $mimeType
     *
     * @return bool
     */
    private static function check(string $mimeType): bool
    {
        return $mimeType === self::$mimeTypeToResolve || str_contains(self::$mimeTypeToResolve, $mimeType);
    }
}
