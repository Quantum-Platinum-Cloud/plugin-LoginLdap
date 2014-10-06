<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\LoginLdap;

use Exception;
use Piwik\Config as PiwikConfig;
use Piwik\Log;
use Piwik\Plugins\LoginLdap\Ldap\ServerInfo;

/**
 * Utility class with methods to manage LoginLdap INI configuration.
 */
class Config
{
    public static $defaultConfig = array(
        'enable_synchronize_access_from_ldap' => 0,
        'new_user_default_sites_view_access' => '',
        'user_email_suffix' => '',
        'required_member_of' => '',
        'ldap_user_filter' => '',
        'ldap_user_id_field' => 'uid',
        'ldap_last_name_field' => 'sn',
        'ldap_first_name_field' => 'givenName',
        'ldap_alias_field' => 'cn',
        'ldap_mail_field' => 'mail',
        'user_email_suffix' => '',
        'ldap_view_access_field' => 'view',
        'ldap_admin_access_field' => 'admin',
        'ldap_superuser_access_field' => 'superuser',
        'use_webserver_auth' => 0,
        'user_access_attribute_server_specification_delimiter',
        'user_access_attribute_server_separator',
        'instance_name'
    );

    // for backwards compatibility
    public static $alternateOptionNames = array(
        'user_email_suffix' => array('usernameSuffix'),
        'required_member_of' => array('memberOf'),
        'ldap_user_filter' => array('filter'),
        'ldap_user_id_field' => array('userIdField'),
        'ldap_alias_field' => array('aliasField'),
        'ldap_mail_field' => array('mailField'),
        'user_email_suffix' => array('usernameSuffix'),
        'use_webserver_auth' => array('useKerberos'),
    );

    /**
     * Returns an INI option value that is stored in the `[LoginLdap]` config section.
     *
     * If alternate option names exist for the option name, they will be used as fallbacks.
     *
     * @param $optionName
     * @return mixed
     */
    public static function getConfigOption($optionName)
    {
        return self::getConfigOptionFrom(PiwikConfig::getInstance()->LoginLdap, $optionName);
    }

    public static function getConfigOptionFrom($config, $optionName)
    {
        if (!empty($config[$optionName])) {
            return $config[$optionName];
        } else if (!empty(self::$alternateOptionNames[$optionName])) {
            foreach (self::$alternateOptionNames[$optionName] as $alternateName) {
                if (!empty($config[$alternateName])) {
                    return $config[$alternateName];
                }
            }
            return null;
        } else {
            return null;
        }
    }

    public static function isAccessSynchronizationEnabled()
    {
        return self::getConfigOption('enable_synchronize_access_from_ldap');
    }

    public static function getDefaultSitesToGiveViewAccessTo()
    {
        return self::getConfigOption('new_user_default_sites_view_access');
    }

    public static function getRequiredMemberOf()
    {
        return self::getConfigOption('required_member_of');
    }

    public static function getLdapUserFilter()
    {
        return self::getConfigOption('ldap_user_filter');
    }

    public static function getLdapUserIdField()
    {
        return self::getConfigOption('ldap_user_id_field');
    }

    public static function getLdapLastNameField()
    {
        return self::getConfigOption('ldap_last_name_field');
    }

    public static function getLdapFirstNameField()
    {
        return self::getConfigOption('ldap_first_name_field');
    }

    public static function getLdapAliasField()
    {
        return self::getConfigOption('ldap_alias_field');
    }

    public static function getLdapMailField()
    {
        return self::getConfigOption('ldap_mail_field');
    }

    public static function getLdapUserEmailSuffix()
    {
        return self::getConfigOption('user_email_suffix');
    }

    public static function getLdapViewAccessField()
    {
        return self::getConfigOption('ldap_view_access_field');
    }

    public static function getLdapAdminAccessField()
    {
        return self::getConfigOption('ldap_admin_access_field');
    }

    public static function getSuperUserAccessField()
    {
        return self::getConfigOption('ldap_superuser_access_field');
    }

    public static function shouldUseWebServerAuthentication()
    {
        return self::getConfigOption('use_webserver_auth') == 1;
    }

    public static function getUserAccessAttributeServerSpecificationDelimiter()
    {
        return self::getConfigOption('user_access_attribute_server_specification_delimiter');
    }

    public static function getUserAccessAttributeServerSiteListSeparator()
    {
        return self::getConfigOption('user_access_attribute_server_separator');
    }

    public static function getDesignatedPiwikInstanceName()
    {
        return self::getConfigOption('instance_name');
    }

    public static function getServerConfig($server)
    {
        $configName = 'LoginLdap_' . $server;
        return PiwikConfig::getInstance()->__get($configName);
    }

    public static function getServerNameList()
    {
        return self::getConfigOption('servers');
    }

    /**
     * Returns a list of {@link ServerInfo} instances describing the LDAP servers
     * that should be connected to.
     *
     * @return ServerInfo[]
     */
    public static function getConfiguredLdapServers()
    {
        $serverNameList = self::getServerNameList();

        if (empty($serverNameList)) {
            $server = ServerInfo::makeFromOldConfig();
            return array($server);
        } else {
            if (is_string($serverNameList)) {
                $serverNameList = explode(',', $serverNameList);
            }

            $servers = array();
            foreach ($serverNameList as $name) {
                try {
                    $servers[] = ServerInfo::makeConfigured($name);
                } catch (Exception $ex) {
                    Log::debug("LoginLdap\\Config::%s: LDAP server info '%s' is configured incorrectly: %s",
                        __FUNCTION__, $name, $ex->getMessage());
                }
            }
            return $servers;
        }
    }

    public static function getPluginOptionValuesWithDefaults()
    {
        $result = self::$defaultConfig;
        foreach ($result as $name => $ignore) {
            $actualValue = Config::getConfigOption($name);
            if (isset($actualValue)) {
                $result[$name] = $actualValue;
            }
        }
        return $result;
    }

    public static function savePluginOptions($config)
    {
        foreach (self::$defaultConfig as $name => $value) {
            if (isset($config[$name])) {
                PiwikConfig::getInstance()->LoginLdap[$name] = $config[$name];
            }
        }

        PiwikConfig::getInstance()->forceSave();
    }

    public static function saveLdapServerConfigs($servers)
    {
        $serverNames = array();
        foreach ($servers as $serverInfo) {
            ServerInfo::saveServerConfig($serverInfo, $forceSave = false);

            $serverNames[] = $serverInfo['name'];
        }
        PiwikConfig::getInstance()->LoginLdap['servers']= $serverNames;

        PiwikConfig::getInstance()->forceSave();
    }
}