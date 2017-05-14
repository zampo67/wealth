<?php

namespace Services\Log;


class Browser
{
    private $_browser_name = '';
    private $_browser_version = '';
    private $_ip = '';
    private $_name_id = 0;
    private $_version_id = 0;

    public function __construct($browser_name,$browser_version,$ip)
    {
        $this->_browser_name = $browser_name;
        $this->_browser_version = $browser_version;
        $this->_ip = $ip;
    }

    public function getBrowserNameId()
    {
        $browser_name_info = \LogRsRecordBrowserNameModel::model()->MFind(array(
            'field' => 'id',
            'where' => array(
                'name' => $this->_browser_name
            )
        ));

        return !empty($browser_name_info) ? $browser_name_info['id'] : $this->saveBrowName();
    }

    public function saveBrowName()
    {
        return \LogRsRecordBrowserNameModel::model()->MSave(array(
            'name' => $this->_browser_name
        ));
    }

    public function getBrowserVersionId()
    {
        $browser_version_info = \LogRsRecordBrowserVersionModel::model()->MFind(array(
            'field' => 'id',
            'where' => array(
                'name_id' => $this->_name_id,
                'version' => $this->_browser_version
            )
        ));

        return !empty($browser_version_info) ? $browser_version_info['id'] : $this->saveBrowVersion();
    }

    public function saveBrowVersion()
    {
        return \LogRsRecordBrowserVersionModel::model()->MSave(array(
            'version' => $this->_browser_version,
            'name_id' => $this->_name_id
        ));
    }

    public function save()
    {
        $this->_name_id = $this->getBrowserNameId();
        $this->_version_id = $this->getBrowserVersionId();

        \LogRsRecordBrowserModel::model()->MSave(array(
            'name_id' => $this->_name_id,
            'version_id' => $this->_version_id,
            'ip' => $this->_ip
        ));
    }
}