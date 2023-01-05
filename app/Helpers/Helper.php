<?php // Code within app\Helpers\Helper.php

namespace App\Helpers;

use App\Models\Contact;
use App\Models\Permissions;
use App\Models\SiteSettings;
use Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Helper
{
    public static function applClasses()
    {
        $siteSetting = SiteSettings::where('name', 'nav_bar')
            ->where('user_id', auth()->id())
            ->first();

        if ($siteSetting) {
            $siteSetting = json_decode($siteSetting->value, true);

        }

        $fullURL = request()->fullurl();
        if (App()->environment() === 'production') {
            for ($i = 1; $i < 7; $i++) {
                $contains = Str::contains($fullURL, 'demo-' . $i);
                $data = config('custom.custom');
                if ($contains === true) {
                    $data = config('custom.' . 'demo-' . $i);
                }
            }

        } else {
            $data = config('custom.custom');
        }

        // default data array
        $DefaultData = [
            'mainLayoutType' => 'vertical',
            'theme' => 'light',
            'sidebarCollapsed' => false,
            'navbarColor' => '',
            'horizontalMenuType' => 'floating',
            'verticalMenuNavbarType' => 'floating',
            'footerType' => 'static', //footer
            'layoutWidth' => 'full',
            'showMenu' => true,
            'bodyClass' => '',
            'bodyStyle' => '',
            'pageClass' => '',
            'pageHeader' => true,
            'contentLayout' => 'default',
            'blankPage' => false,
            'defaultLanguage' => 'en',
            'direction' => env('MIX_CONTENT_DIRECTION', 'ltr'),
        ];

        // if any key missing of array from custom.php file it will be merge and set a default value from dataDefault array and store in data variable
        $data = array_merge($DefaultData, $data);

        // All options available in the template
        $allOptions = [
            'mainLayoutType' => array('vertical', 'horizontal'),
            'theme' => array('light' => 'light', 'dark' => 'dark-layout', 'bordered' => 'bordered-layout', 'semi-dark' => 'semi-dark-layout'),
            'sidebarCollapsed' => array(true, false),
            'showMenu' => array(true, false),
            'layoutWidth' => array('full', 'boxed'),
            'navbarColor' => array('bg-primary', 'bg-info', 'bg-warning', 'bg-success', 'bg-danger', 'bg-dark'),
            'horizontalMenuType' => array('floating' => 'navbar-floating', 'static' => 'navbar-static', 'sticky' => 'navbar-sticky'),
            'horizontalMenuClass' => array('static' => '', 'sticky' => 'fixed-top', 'floating' => 'floating-nav'),
            'verticalMenuNavbarType' => array('floating' => 'navbar-floating', 'static' => 'navbar-static', 'sticky' => 'navbar-sticky', 'hidden' => 'navbar-hidden'),
            'navbarClass' => array('floating' => 'floating-nav', 'static' => 'navbar-static-top', 'sticky' => 'fixed-top', 'hidden' => 'd-none'),
            'footerType' => array('static' => 'footer-static', 'sticky' => 'footer-fixed', 'hidden' => 'footer-hidden'),
            'pageHeader' => array(true, false),
            'contentLayout' => array('default', 'content-left-sidebar', 'content-right-sidebar', 'content-detached-left-sidebar', 'content-detached-right-sidebar'),
            'blankPage' => array(false, true),
            'sidebarPositionClass' => array('content-left-sidebar' => 'sidebar-left', 'content-right-sidebar' => 'sidebar-right', 'content-detached-left-sidebar' => 'sidebar-detached sidebar-left', 'content-detached-right-sidebar' => 'sidebar-detached sidebar-right', 'default' => 'default-sidebar-position'),
            'contentsidebarClass' => array('content-left-sidebar' => 'content-right', 'content-right-sidebar' => 'content-left', 'content-detached-left-sidebar' => 'content-detached content-right', 'content-detached-right-sidebar' => 'content-detached content-left', 'default' => 'default-sidebar'),
            'defaultLanguage' => array('en' => 'en', 'fr' => 'fr', 'de' => 'de', 'pt' => 'pt'),
            'direction' => array('ltr', 'rtl'),
        ];

        //if mainLayoutType value empty or not match with default options in custom.php config file then set a default value
        foreach ($allOptions as $key => $value) {
            if (array_key_exists($key, $DefaultData)) {
                if (gettype($DefaultData[$key]) === gettype($data[$key])) {
                    // data key should be string
                    if (is_string($data[$key])) {
                        // data key should not be empty
                        if (isset($data[$key]) && $data[$key] !== null) {
                            // data key should not be exist inside allOptions array's sub array
                            if (!array_key_exists($data[$key], $value)) {
                                // ensure that passed value should be match with any of allOptions array value
                                $result = array_search($data[$key], $value, 'strict');
                                if (empty($result) && $result !== 0) {
                                    $data[$key] = $DefaultData[$key];
                                }
                            }
                        } else {
                            // if data key not set or
                            $data[$key] = $DefaultData[$key];
                        }
                    }
                } else {
                    $data[$key] = $DefaultData[$key];
                }
            }
        }

        $data['theme'] = isset($siteSetting['skin']) ? $siteSetting['skin'] : $data['theme'];
        $data['layoutWidth'] = isset($siteSetting['layoutWidth']) ? $siteSetting['layoutWidth'] : $data['layoutWidth'];
        $data['navbarColor'] = isset($siteSetting['navColor']) ? $siteSetting['navColor'] : $data['navbarColor'];
        $data['verticalMenuNavbarType'] = isset($siteSetting['navType']) ? $siteSetting['navType'] : $data['verticalMenuNavbarType'];
        $data['footerType'] = isset($siteSetting['footerType']) ? $siteSetting['footerType'] : $data['footerType'];
        $data['sidebarCollapsed'] = isset($siteSetting['collapse_sidebar']) ? $siteSetting['collapse_sidebar'] : $data['sidebarCollapsed'];
        $data['showMenu'] = isset($siteSetting['showMenu']) ? $siteSetting['showMenu'] : $data['showMenu'];

        //layout classes
        $layoutClasses = [
            'theme' => $data['theme'],
            'layoutTheme' => $allOptions['theme'][$data['theme']],
            'sidebarCollapsed' => $data['sidebarCollapsed'],
            'showMenu' => $data['showMenu'],
            'layoutWidth' => $data['layoutWidth'],
            'verticalMenuNavbarType' => $allOptions['verticalMenuNavbarType'][$data['verticalMenuNavbarType']],
            'navbarClass' => $allOptions['navbarClass'][$data['verticalMenuNavbarType']],
            'navbarColor' => $data['navbarColor'],
            'horizontalMenuType' => $allOptions['horizontalMenuType'][$data['horizontalMenuType']],
            'horizontalMenuClass' => $allOptions['horizontalMenuClass'][$data['horizontalMenuType']],
            'footerType' => $allOptions['footerType'][$data['footerType']],
            'sidebarClass' => 'menu-expanded',
            'bodyClass' => $data['bodyClass'],
            'bodyStyle' => $data['bodyStyle'],
            'pageClass' => $data['pageClass'],
            'pageHeader' => $data['pageHeader'],
            'blankPage' => $data['blankPage'],
            'blankPageClass' => '',
            'contentLayout' => $data['contentLayout'],
            'sidebarPositionClass' => $allOptions['sidebarPositionClass'][$data['contentLayout']],
            'contentsidebarClass' => $allOptions['contentsidebarClass'][$data['contentLayout']],
            'mainLayoutType' => $data['mainLayoutType'],
            'defaultLanguage' => $allOptions['defaultLanguage'][$data['defaultLanguage']],
            'direction' => $data['direction'],
        ];
        // set default language if session hasn't locale value the set default language
        if (!session()->has('locale')) {
            app()->setLocale($layoutClasses['defaultLanguage']);
        }

        // sidebar Collapsed
        if ($layoutClasses['sidebarCollapsed'] == 'true') {
            $layoutClasses['sidebarClass'] = "menu-collapsed";
        }

        // blank page class
        if ($layoutClasses['blankPage'] == 'true') {
            $layoutClasses['blankPageClass'] = "blank-page";
        }

        return $layoutClasses;
    }

    public static function updatePageConfig($pageConfigs)
    {
        $demo = 'custom';
        $fullURL = request()->fullurl();
        if (App()->environment() === 'production') {
            for ($i = 1; $i < 7; $i++) {
                $contains = Str::contains($fullURL, 'demo-' . $i);
                if ($contains === true) {
                    $demo = 'demo-' . $i;
                }
            }
        }
        if (isset($pageConfigs)) {
            if (count($pageConfigs) > 0) {
                foreach ($pageConfigs as $config => $val) {
                    Config::set('custom.' . $demo . '.' . $config, $val);
                }
            }
        }
    }

    public static function getSettings($name = 'nav_bar')
    {
        $siteSetting = SiteSettings::where('name', 'nav_bar')
            ->where('user_id', auth()->id())
            ->first();
        if ($siteSetting) {
            $siteSetting = json_decode($siteSetting->value);

        } else {
            $siteSetting = config('custom');
        }

        return $siteSetting;
    }

    public static function get_user_permissions($id = "0")
    {
        $user_id = auth('sanctum')->user()->id;
        $per = Permissions::where(['user_id' => $user_id, 'permission_id' => $id])->get()->first();

        if (isset($per->id)) {
            return 1;
        } else {
            return 0;
        }

        return false;

        if (isset($per->id)) {
            echo json_encode(array('status' => "1", 'msg' => "Success"));
        } else {
            echo json_encode(array('status' => "0", 'msg' => "Error"));
        }

    }

    public static function get_contact_count()
    {
        if (self::get_user_permissions(3) == 1) {
            return Contact::where('IsCase', 0)
                ->where('read_at', 0)
                ->count();

        }
        return 0;
    }

    public static function get_contacts()
    {
        if (self::get_user_permissions(3) == 1) {
            return Contact::where('IsCase', 0)
                ->orderBy('ContactID', 'DESC')
                ->where('read_at', 0)
                ->paginate(5);

        } else {
            return collect();
        }
    }

    public static function getUserPermissions($userId = "", $permissionId = 0)
    {
        if ($userId) {
            $permission = Permissions::where(['user_id' => $userId, 'permission_id' => $permissionId])->first();

            if ($permission && $permission->id) {
                return 1;
            }
            return 0;
        }

        return 0;
    }
}
