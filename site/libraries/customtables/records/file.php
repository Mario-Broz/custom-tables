<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use ESFileUploader;
use Exception;

class Save_file
{
    var CT $ct;
    public Field $field;
    var ?array $row_new;

    function __construct(CT &$ct, Field $field, ?array $row_new)
    {
        $this->ct = &$ct;
        $this->field = $field;
        $this->row_new = $row_new;
    }

    /**
     * @throws Exception
     * @since 3.3.3
     */
    function saveFieldSet(?string $listing_id): ?array
    {
        $newValue = null;


        //Under question
        $file_type_file = CUSTOMTABLES_LIBRARIES_PATH . DIRECTORY_SEPARATOR . 'fieldtypes' . DIRECTORY_SEPARATOR . '_type_file.php';
        require_once($file_type_file);

        $to_delete = common::inputPostCmd($this->field->comesfieldname . '_delete', null, 'create-edit-record');

        //Get new file
        $fileId = null;

        if (defined('_JEXEC')) {
            $fileId = common::inputPostString($this->field->comesfieldname, null, 'create-edit-record');
        } elseif (defined('WPINC')) {
            //Get new image
            if (isset($_FILES[$this->field->comesfieldname]))
                $fileId = $_FILES[$this->field->comesfieldname]['tmp_name'];
        }
        //Set the variable to "false" to do not delete existing file
        $deleteExistingFile = false;

        $FileFolder = FileUtils::getOrCreateDirectoryPath($this->field->params[1]);

        if ($fileId !== null and $fileId != '') {
            //Upload new file

            if ($listing_id == 0) {
                $fileSystemFileFolder = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, CUSTOMTABLES_ABSPATH . $FileFolder);
                $value = $this->UploadSingleFile(null, $fileId, $fileSystemFileFolder);
            } else {
                $ExistingFile = $this->field->ct->Table->getRecordFieldValue($listing_id, $this->field->realfieldname);

                $fileSystemFileFolder = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, CUSTOMTABLES_ABSPATH . $FileFolder);
                $value = $this->UploadSingleFile($ExistingFile, $fileId, $fileSystemFileFolder);
            }

            //Set new image value
            if ($value)
                $newValue = ['value' => $value];

            $deleteExistingFile = true;
        } elseif ($to_delete == 'true') {
            $newValue = ['value' => null];//This way it will be clear if the value changed or not. If $this->newValue = null means that value not changed.
            $deleteExistingFile = true;
        }

        if ($deleteExistingFile) {

            $ExistingFile = $this->field->ct->Table->getRecordFieldValue($listing_id, $this->field->realfieldname);

            $filepath = str_replace('/', DIRECTORY_SEPARATOR, $FileFolder);
            if (substr($filepath, 0, 1) == DIRECTORY_SEPARATOR)
                $filepath = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, CUSTOMTABLES_ABSPATH . $filepath);
            else
                $filepath = CUSTOMTABLES_ABSPATH . $filepath;

            if ($ExistingFile != '' and !self::checkIfTheFileBelongsToAnotherRecord($ExistingFile, $this->field)) {
                $filename_full = $filepath . DIRECTORY_SEPARATOR . $ExistingFile;

                if (file_exists($filename_full))
                    unlink($filename_full);
            }
        }
        return $newValue;
    }

    /**
     * @throws Exception
     * @since 3.3.4
     */
    private function UploadSingleFile(?string $ExistingFile, string $file_id, string $FileFolder): ?string
    {
        if ($this->field->type == 'file')
            $fileExtensions = $this->field->params[2] ?? '';
        elseif ($this->field->type == 'blob')
            $fileExtensions = $this->field->params[1] ?? '';
        else
            return null;

        if ($file_id != '') {
            if (empty($this->field->params[3]))
                $new_filename_temp = $file_id;
            else
                $new_filename_temp = $this->field->params[3];

            $accepted_file_types = explode(' ', ESFileUploader::getAcceptedFileTypes($fileExtensions));
            $accepted_filetypes = array();

            foreach ($accepted_file_types as $filetype) {
                $mime = ESFileUploader::get_mime_type('1.' . $filetype);
                $accepted_filetypes[] = $mime;

                if ($filetype == 'docx')
                    $accepted_filetypes[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                elseif ($filetype == 'xlsx')
                    $accepted_filetypes[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                elseif ($filetype == 'pptx')
                    $accepted_filetypes[] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            }

            $uploadedFile = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $file_id;

            $is_base64encoded = common::inputGetCmd('base64encoded', '');
            if ($is_base64encoded == "true") {
                $src = $uploadedFile;

                $file = common::inputPostString($this->field->comesfieldname, '');
                $dst = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'decoded_' . basename($file['name']);
                common::base64file_decode($src, $dst);
                $uploadedFile = $dst;
            }

            if (!empty($ExistingFile) and !$this->checkIfTheFileBelongsToAnotherRecord($ExistingFile)) {
                //Delete Old File
                $filename_full = $FileFolder . DIRECTORY_SEPARATOR . $ExistingFile;

                if (file_exists($filename_full))
                    unlink($filename_full);
            }

            if (!file_exists($uploadedFile))
                return null;

            $mime = mime_content_type($uploadedFile);

            $parts = explode('.', $uploadedFile);
            $fileExtension = end($parts);
            if ($mime == 'application/zip' and $fileExtension != 'zip') {
                //could be docx, xlsx, pptx
                $mime = ESFileUploader::checkZIPfile_X($uploadedFile, $fileExtension);
            }

            if (in_array($mime, $accepted_filetypes)) {
                $new_filename = self::getCleanAndAvailableFileName($new_filename_temp, $FileFolder);
                $new_filename_path = str_replace('/', DIRECTORY_SEPARATOR, $FileFolder . DIRECTORY_SEPARATOR . $new_filename);

                if (@copy($uploadedFile, $new_filename_path)) {
                    unlink($uploadedFile);
                    //Copied
                    return $new_filename;
                } else {
                    unlink($uploadedFile);
                    //Cannot copy
                    return null;
                }
            } else {
                unlink($uploadedFile);
                return null;
            }
        }
        return null;
    }

    /**
     * @throws Exception
     * @since 3.3.4
     */
    private function checkIfTheFileBelongsToAnotherRecord(string $filename): bool
    {
        $whereClause = new MySQLWhereClause();
        $whereClause->addCondition($this->field->realfieldname, $filename);
        $col = database::loadColumn($this->field->ct->Table->realtablename, ['COUNT_ROWS'], $whereClause, null, null, 2);
        if (count($col) == 0)
            return false;

        return $col[0] > 1;
    }

    protected static function getCleanAndAvailableFileName(string $desiredFileName, string $FileFolder): string
    {
        //Clean Up file name
        $rawFileName = str_replace(' ', '_', $desiredFileName);

        // Process field name
        if (function_exists("transliterator_transliterate"))
            $rawFileName = transliterator_transliterate("Any-Latin; Latin-ASCII;", $rawFileName);

        $rawFileName = preg_replace("/[^\p{L}\d._]/u", "", $rawFileName);

        $fileNameParts = explode('.', $rawFileName);
        $fileExtension = end($fileNameParts);
        unset($fileNameParts[count($fileNameParts) - 1]);
        $fileName = implode($fileNameParts);

        $i = 0;
        $new_fileName = $fileName . '.' . $fileExtension;
        while (1) {

            if (file_exists($FileFolder . DIRECTORY_SEPARATOR . $new_fileName)) {
                //increase index
                $i++;
                $new_fileName = $fileName . '_' . $i . '.' . $fileExtension;
            } else
                break;
        }
        return $new_fileName;
    }
}
