<?php

namespace App\Http\Controllers\Filebase;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class FilebaseController extends BaseController
{
    /**
     * @return \Filebase\Database
     * @throws \Filebase\Filesystem\FilesystemException
     */
    public static function getDB()
    {
        return new \Filebase\Database(['dir' => base_path() . '/database/filebase']);
    }

    /**
     * @return \Filebase\Document
     * @throws \Filebase\Filesystem\FilesystemException
     */
    public static function getPreferences()
    {
        $db = self::getDB();
        return $db->get('preferences');
    }

    /**
     * @param $field
     * @return array|string|bool
     * @throws \Filebase\Filesystem\FilesystemException
     */
    public static function getField($field)
    {
        $db = self::getDB();

        $data = $db->get('preferences')->field($field);

        if (\strpos($field, 'favorite') !== false) {
            $data = explode(',', $data);

            if ($data === "") {
                $data = [];
            }

        }

        return $data;
    }

    /**
     * @param $field
     * @param $value
     * @throws \Filebase\Filesystem\FilesystemException
     */
    public static function setField($field, $value)
    {
        $db = self::getPreferences();
        $db->$field = $value;

        $db->save();
    }
}
