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
                "Setting to true disables the module"
            ),
            "Host" => new SettingsProperty(
                "127.0.0.1",
                "string",
                null,
                "LDAP server host"
            ),
            "Port" => new SettingsProperty(
                389,
                "int",
                null,
                "LDAP server port"
            ),
            "SearchDn" => new SettingsProperty(
                "ou=users,dc=example,dc=org",
                "string",
                null,
                "Base Search DN for users lookup"
            ),
            "BindDn" => new SettingsProperty(
                "cn=admin,dc=example,dc=org",
                "string",
                null,
                "Bind DN used for authentication"
            ),
            "BindPassword" => new SettingsProperty(
                "adminpassword",
                "string",
                null,
                "Password used for authentication on LDAP server. Will be automatically encrypted"
            ),
            "ContactObjectClass" => new SettingsProperty(
                "posixAccount",
                "string",
                null,
                "Object class used for user lookup"
            ),
            "UidFieldName" => new SettingsProperty(
                "uid",
                "string",
                null,
                "Denotes the field used as UID"
            ),
            "EmailFieldName" => new SettingsProperty(
                "mail",
                "string",
                null,
                "Denotes the field used as email address"
            ),
            "NameFieldName" => new SettingsProperty(
                "cn",
                "string",
                null,
                "Denotes the field used as name"
            ),
            "SkipEmptyEmail" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, only users with email addresses will be listed"
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
                "Mapping between LDAP fields and contact fields"
            )
        ];
    }
}
