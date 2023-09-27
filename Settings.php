<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\TeamContactsLdap;

use Aurora\System\SettingsProperty;

/**
 * @property bool $Disabled
 * @property string $Host
 * @property int $Port
 * @property string $SearchDn
 * @property string $BindDn
 * @property string $BindPassword
 * @property string $ContactObjectClass
 * @property string $UidFieldName
 * @property string $EmailFieldName
 * @property string $NameFieldName
 * @property bool $SkipEmptyEmail
 * @property array $ContactMap
 */

class Settings extends \Aurora\System\Module\Settings
{
    protected function initDefaults()
    {
        $this->aContainer = [
            "Disabled" => new SettingsProperty(
                false,
                "bool",
                null,
                ""
            ),
            "Host" => new SettingsProperty(
                "127.0.0.1",
                "string",
                null,
                ""
            ),
            "Port" => new SettingsProperty(
                389,
                "int",
                null,
                ""
            ),
            "SearchDn" => new SettingsProperty(
                "ou=users,dc=example,dc=org",
                "string",
                null,
                ""
            ),
            "BindDn" => new SettingsProperty(
                "cn=admin,dc=example,dc=org",
                "string",
                null,
                ""
            ),
            "BindPassword" => new SettingsProperty(
                "adminpassword",
                "string",
                null,
                ""
            ),
            "ContactObjectClass" => new SettingsProperty(
                "posixAccount",
                "string",
                null,
                ""
            ),
            "UidFieldName" => new SettingsProperty(
                "uid",
                "string",
                null,
                ""
            ),
            "EmailFieldName" => new SettingsProperty(
                "mail",
                "string",
                null,
                ""
            ),
            "NameFieldName" => new SettingsProperty(
                "cn",
                "string",
                null,
                ""
            ),
            "SkipEmptyEmail" => new SettingsProperty(
                true,
                "bool",
                null,
                ""
            ),
            "ContactMap" => new SettingsProperty(
                [
                    "displayName" => "FullName",
                    "cn" => "FullName",
                    "mail" => "BusinessEmail",
                    "title" => "BusinessJobTitle",
                    "company" => "BusinessCompany",
                    "department" => "BusinessDepartment",
                    "telephoneNumber" => "BusinessPhone",
                    "physicalDeliveryOfficeName" => "BusinessOffice"
                ],
                "array",
                null,
                ""
            )
        ];
    }
}
