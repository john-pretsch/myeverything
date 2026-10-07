<?php

namespace App\Services\Resumes;

class ResumeImageRewriter
{
    /**
     * Point relative <img src> values at images in public/assets/images.
     * `profile.*` maps to the owner's uploaded profile image; any other
     * relative name is used only if a file of that name exists there.
     */
    public function rewrite(string $html, int $userId): string
    {
        return preg_replace_callback(
            '/(<img\b[^>]*?\bsrc=)(["\'])([^"\']+)\2/i',
            function (array $m) use ($userId) {
                $src = $m[3];

                if (preg_match('#^([a-z][a-z0-9+.-]*:|//|/|data:)#i', $src)) {
                    return $m[0];
                }

                $name = basename($src);
                $file = preg_match('/^profile\.(png|jpe?g)$/i', $name)
                    ? ProfileImageProcessor::filenameFor($userId)
                    : $name;
                $path = ProfileImageProcessor::directory().'/'.$file;

                if (! is_file($path)) {
                    return $m[0];
                }

                return $m[1].$m[2].url('/api/assets/images/'.rawurlencode($file)).$m[2];
            },
            $html,
        ) ?? $html;
    }
}
