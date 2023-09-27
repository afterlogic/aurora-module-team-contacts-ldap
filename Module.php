<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\TeamContactsLdap;

use Aurora\Modules\Contacts;
use Aurora\Modules\Contacts\Models\Contact;
use Aurora\Modules\Contacts\Enums\StorageType;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @property Settings $oModuleSettings
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
    protected static $iStorageOrder = 20;

    /**
     * @var string
     */
    private $sContactObjectClass;

    /**
     * @var string
     */
    private $sContactObjectClassForSearch;

    /**
     * @var string
     */
    private $sUidFieldName;

    /**
     * @var string
     */
    private $sEmailFieldName;

    /**
     * @var string
     */
    private $sNameFieldName;

    /**
     * @var bool
     */
    private $bSkipEmptyEmail;


    /**
     * Creates if it is necessary and returns ldap connector.
     *
     * @return \Aurora\System\Utils\Ldap|bool
     */
    private function getLdapConnector()
    {
        static $oLdap = null;
        if (null === $oLdap) {
            $oLdap = new \Aurora\System\Utils\Ldap($this->oModuleSettings->SearchDn);
            $oLdap = $oLdap->Connect(
                $this->oModuleSettings->Host,
                $this->oModuleSettings->Port,
                $this->oModuleSettings->BindDn,
                $this->oModuleSettings->BindPassword
            ) ? $oLdap : false;
        }

        return $oLdap;
    }

    /**
     * Returns search string for ldap request.
     *
     * @param \Aurora\System\Utils\Ldap $oLdap Ldap connector.
     * @param string $sSearch Search string.
     *
     * @return string
     */
    private function getSearchLdapRequest($oLdap, $sSearch)
    {
        $sName = 0 < strlen($this->sNameFieldName) ? '('.$this->sNameFieldName.'=*'.$oLdap->Escape($sSearch).'*)' : '';
        $sEmail = 0 < strlen($this->sEmailFieldName) ? '('.$this->sEmailFieldName.'=*'.$oLdap->Escape($sSearch).'*)' : '';
        return 0 < strlen($sName.$sEmail) ? '(|'.$sName.$sEmail.')' : '';
    }

    /**
     * Obtains count of all global contacts found by search string for specified account.
     *
     * @param string $sSearch = '' Search string.
     *
     * @return int
     */
    public function getContactItemsCount($sSearch)
    {
        $oLdap = $this->getLdapConnector();

        if ($oLdap) {
            $sFilter = $this->sContactObjectClassForSearch;
            if (0 < strlen($sSearch)) {
                $sFilter = '(&'.$this->getSearchLdapRequest($oLdap, $sSearch).$sFilter.')';
            }

            return $oLdap->Search($sFilter) ? $oLdap->ResultCount() : 0;
        }

        return 0;
    }

    /**
     * Obtains all global contacts by search string for specified user.
     *
     * @param \Aurora\Modules\Core\Models\User $oUser User object.
     * @param int $iSortField Sort field for sorting contact list.
     * @param int $iSortOrder Sort order for sorting contact list.
     * @param int $iOffset Offset value for obtaining a partial list.
     * @param int $iRequestLimit Limit value for obtaining a partial list.
     * @param string $sSearch Search string.
     *
     * @return bool|array
     */
    public function getContactItems($oUser, $iSortField, $iSortOrder, $iOffset, $iRequestLimit, $sSearch)
    {
        $oLdap = $this->getLdapConnector();

        $aContacts = [];

        if ($oLdap) {
            $sFilter = $this->sContactObjectClassForSearch;
            if (0 < strlen($sSearch)) {
                $sFilter = '(&'.$this->getSearchLdapRequest($oLdap, $sSearch).$sFilter.')';
            }

            if ($oLdap->Search($sFilter)) {
                $aReturn = $oLdap->SortPaginate(
                    Contacts\Enums\SortField::Email === $iSortField ? $this->sEmailFieldName : $this->sNameFieldName,
                    \Aurora\System\Enums\SortOrder::ASC === $iSortOrder,
                    $iOffset,
                    $iRequestLimit
                );

                if ($aReturn && is_array($aReturn) && 0 < count($aReturn)) {
                    foreach ($aReturn as $aItem) {
                        if (is_array($aItem)) {
                            $aItem = array_change_key_case($aItem, CASE_LOWER);
                            if (isset($aItem[$this->sUidFieldName][0])) {
                                $sEmail = !empty($aItem[$this->sEmailFieldName][0]) ? $aItem[$this->sEmailFieldName][0] : '';
                                $aContacts[] = array(
                                    'UUID' => $aItem[$this->sUidFieldName][0],
                                    'IdUser' => 0,
                                    'FullName' => isset($aItem[$this->sNameFieldName][0]) ? $aItem[$this->sNameFieldName][0] : '',
                                    'FirstName' => '',
                                    'LastName' => '',
                                    'ViewEmail' => $sEmail,
                                    'Storage' => StorageType::Team,
                                    'Frequency' => 0,
                                    'DateModified' => 0,
                                    'ETag' => "",
                                    'ItsMe' => $sEmail === $oUser->PublicId
                                );
                            }
                        }
                    }
                }
            }
        }

        return $aContacts;
    }

    private function getContactMap()
    {
        return $this->oModuleSettings->ContactMap;
    }

    private function populateResultContact($oLdap, $oUser)
    {
        $aItem = $oLdap->ResultItem();

        $oContact = false;

        if (false === $oContact && is_array($aItem)) {
            $aMap = $this->getContactMap();

            $aMap = array_change_key_case($aMap, CASE_LOWER);
            $aItem = array_change_key_case($aItem, CASE_LOWER);

            //print_r($aMap); exit();
            $sId = $aItem[$this->sUidFieldName][0];
            if (isset($sId)) {
                $oContact = new \Aurora\Modules\Contacts\Models\Contact();
                $aContact = array(
                    'UUID' => $sId,
                    'IdUser' => $oUser->Id,
                    'UseFriendlyName' => true,
                    'Storage' => StorageType::Team,
                    'ReadOnly' => true,
                    'Global' => true,
                    'IdContact' => $sId,
                    'IdContactStr' => $sId,
                    'PrimaryEmail' => Contacts\Enums\PrimaryEmail::Business
                );

                foreach ($aMap as $sKey => $sField) {
                    if (isset($aItem[$sKey]) && (!isset($aContact[$sField]) || 0 === strlen($aContact[$sField]))) {
                        $aContact[$sField] = isset($aItem[$sKey][0]) ? $aItem[$sKey][0] : '';
                    }
                }

                $sEmail = "";
                if ((isset($aContact["PersonalEmail"]) && ($aContact["PersonalEmail"])!=="")) {
                    $sEmail = $aContact["PersonalEmail"];
                }
                if ((isset($aContact["BusinessEmail"]) && ($aContact["BusinessEmail"])!=="")) {
                    $sEmail = $aContact["BusinessEmail"];
                }
                $aContact['ViewEmail'] = $sEmail;
                $aContact['ItsMe'] = $sEmail === $oUser->PublicId;

                $oContact->populate($aContact, true);
            }
        }

        return $oContact ? $oContact : false;
    }

    public function init()
    {
        $this->sContactObjectClass = strtolower($this->oModuleSettings->ContactObjectClass);
        $this->sUidFieldName = strtolower($this->oModuleSettings->UidFieldName);
        $this->sEmailFieldName = strtolower($this->oModuleSettings->EmailFieldName);
        $this->sNameFieldName = strtolower($this->oModuleSettings->NameFieldName);
        $this->bSkipEmptyEmail = $this->oModuleSettings->SkipEmptyEmail;

        $this->sContactObjectClassForSearch = '(objectClass='.$this->sContactObjectClass.')';
        if ($this->bSkipEmptyEmail && 0 < strlen($this->sEmailFieldName)) {
            $this->sContactObjectClassForSearch = '(&'.$this->sContactObjectClassForSearch.'('.$this->sEmailFieldName.'=*@*))';
        }

        $this->subscribeEvent('Contacts::GetStorages', array($this, 'onGetStorages'));
        $this->subscribeEvent('Contacts::GetContacts::after', array($this, 'onAfterGetContacts'));
        $this->subscribeEvent('Contacts::GetContact::after', array($this, 'onAfterGetContact'));
        $this->subscribeEvent('Contacts::PrepareFiltersFromStorage', array($this, 'prepareFiltersFromStorage'));
    }

    public function onGetStorages(&$aStorages)
    {
        $aStorages[self::$iStorageOrder] = StorageType::Team;
    }

    public function onAfterGetContacts($aArgs, &$mResult)
    {
        if ($aArgs['Storage'] === StorageType::Team || $aArgs['Storage'] === StorageType::All) {
            if ($aArgs['Storage'] === StorageType::Team) {
                $mResult = [
                    'ContactCount' => 0,
                    'List' => []
                ];
            }
            $iOffset = isset($aArgs['Offset']) ? $aArgs['Offset'] : null;
            $iLimit = isset($aArgs['Limit']) ? $aArgs['Limit'] : null;
            $SortOrder = isset($aArgs['SortOrder']) ? $aArgs['SortOrder'] : \Aurora\System\Enums\SortOrder::ASC;

            $oUser = \Aurora\Api::getAuthenticatedUser();
            $mResult['ContactCount'] +=  $this->getContactItemsCount($aArgs['Search']);
            $mResult['List'] = \array_merge(
                $mResult['List'],
                $this->getContactItems($oUser, $aArgs['SortField'], $SortOrder, $iOffset, $iLimit, $aArgs['Search'])
            );
        }
    }

    public function onAfterGetContact($aArgs, &$mResult)
    {
        if (!$mResult) {
            $oLdap = $this->getLdapConnector();

            $mResult = false;
            $mContactId = $aArgs['UUID'];
            $oUser = \Aurora\Api::getAuthenticatedUser();
            if ($oLdap && $oLdap->Search('(&'.$this->sContactObjectClassForSearch.'('.$this->sUidFieldName.'='.$oLdap->Escape($mContactId).'))')) {
                $mResult = $this->populateResultContact($oLdap, $oUser);
            }
        }
    }

    public function prepareFiltersFromStorage(&$aArgs, &$mResult)
    {
        if (isset($aArgs['Storage']) && ($aArgs['Storage'] === StorageType::Team)) {
            $aArgs['IsValid'] = true;

            if (!isset($mResult)) {
                $mResult = \Aurora\Modules\Contacts\Models\Contact::query();
            }

            $oUser = \Aurora\System\Api::getAuthenticatedUser();

            $mResult = $mResult->orWhere(function ($query) use ($oUser) {
                $query = $query->where('IdTenant', $oUser->IdTenant)
                    ->where('Storage', StorageType::Team);
            });
        }
    }
}
