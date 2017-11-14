<?php
// @codingStandardsIgnoreFile

namespace Jnjxp\WebUi\Input;

use Psr\Http\Message\UploadedFileInterface as UploadedFile;
use Psr\Http\Message\ServerRequestInterface as Request;
use SplFileInfo;
use RuntimeException;


/**
 * Dont let PSR7 cross the domain boundary
 */
trait GetFilesTrait
{
    // per request tmp dir
    protected $tmpdir;


    // Get an array of SplFileInfo based on uploaded files
    protected function getFiles(Request $request)
    {
        $files    = [];
        $uploaded = $request->getUploadedFiles();

        foreach ($uploaded as $key => $upload) {
            $files[$key] = $this->getFile($upload);
        }

        return $files;
    }

    // Convert an UploadedFileInterface to an SplFileInfo
    protected function getFile(UploadedFile $upload)
    {
        try {
            $tmpdir      = $this->tmpdir();
            $filename    = $upload->getClientFilename();
            $destination = $tmpdir . '/' . $filename;
            $upload->moveTo($destination);

            return new SplFileInfo($destination);

        } catch (RuntimeException $e) {
            return null;
        }
    }

    // Create a temp directory to store all the uploads from this request
    protected function tmpdir()
    {

        if (! $this->tmpdir) {
            $tmpdir = tempnam(sys_get_temp_dir(), 'upload-');

            if (file_exists($tmpdir)) {
                unlink($tmpdir);
            }

            mkdir($tmpdir);
            $this->tmpdir = $tmpdir;
        }

        return $this->tmpdir;
    }
}
