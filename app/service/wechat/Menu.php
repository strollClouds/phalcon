<?php

namespace Service\Wechat;

use Service\Wechat\Auth;
use Service\Wechat\Common\Curl;
use Service\Wechat\Common\Utils;

/**
 * 菜单
 */
class Menu
{
	/**
     * 添加菜单，一级菜单最多3个，每个一级菜单最多可以有5个二级菜单
     * @param $menuList
     *          array(
     *              array('id'=>'', 'pid'=>'', 'name'=>'', 'type'=>'', 'code'=>''),
     *              array('id'=>'', 'pid'=>'', 'name'=>'', 'type'=>'', 'code'=>''),
     *              array('id'=>'', 'pid'=>'', 'name'=>'', 'type'=>'', 'code'=>''),
     *          );
     *          'code'是view类型的URL或者其他类型的key
     *          'type'是菜单类型，如下:
     *              1、click：点击推事件，用户点击click类型按钮后，微信服务器会通过消息接口推送消息类型为event的结构给开发者（参考消息接口指南），并且带上按钮中开发者填写的key值，开发者可以通过自定义的key值与用户进行交互；
     *              2、view：跳转URL，用户点击view类型按钮后，微信客户端将会打开开发者在按钮中填写的网页URL，可与网页授权获取用户基本信息接口结合，获得用户基本信息。
     *              3、scancode_push：扫码推事件，用户点击按钮后，微信客户端将调起扫一扫工具，完成扫码操作后显示扫描结果（如果是URL，将进入URL），且会将扫码的结果传给开发者，开发者可以下发消息。
     *              4、scancode_waitmsg：扫码推事件且弹出“消息接收中”提示框，用户点击按钮后，微信客户端将调起扫一扫工具，完成扫码操作后，将扫码的结果传给开发者，同时收起扫一扫工具，然后弹出“消息接收中”提示框，随后可能会收到开发者下发的消息。
     *              5、pic_sysphoto：弹出系统拍照发图，用户点击按钮后，微信客户端将调起系统相机，完成拍照操作后，会将拍摄的相片发送给开发者，并推送事件给开发者，同时收起系统相机，随后可能会收到开发者下发的消息。
     *              6、pic_photo_or_album：弹出拍照或者相册发图，用户点击按钮后，微信客户端将弹出选择器供用户选择“拍照”或者“从手机相册选择”。用户选择后即走其他两种流程。
     *              7、pic_weixin：弹出微信相册发图器，用户点击按钮后，微信客户端将调起微信相册，完成选择操作后，将选择的相片发送给开发者的服务器，并推送事件给开发者，同时收起相册，随后可能会收到开发者下发的消息。
     *              8、location_select：弹出地理位置选择器，用户点击按钮后，微信客户端将调起地理位置选择工具，完成选择操作后，将选择的地理位置发送给开发者的服务器，同时收起位置选择工具，随后可能会收到开发者下发的消息。
     *
     * @return bool
     */
	public static function setMenu(Array $menus)
	{
		$tree = self::_generateTree($menus);
		$data['button'] = $tree;
        //转换成JSON
        $data = json_encode($data);
        $data = urldecode($data);
        $accessToken = Auth::getBaseAccessToken();
        $url = Utils::getConfig()->setMenu . 'access_token=' . $accessToken;
        $result = Curl::callWebServer($url, $data, 'POST');
        if($result['errcode'] == 0){
            return true;
        }
        return $result;
	}

	public static function getMenu()
	{
		$accessToken = Auth::getBaseAccessToken();
        $url = Utils::getConfig()->getMenu . 'access_token='.$accessToken;
        return Curl::callWebServer($url, '', 'GET');
	}

	private static function &_generateTree(&$menus)
	{
		$tmp = [];
		foreach ($menus as &$menu) 
		{
			$menu['name'] = urlencode($menu['name']);
			$tmp[$menu['id']] = $menu;
		}

		$tree = [];
		
		foreach($tmp as $item)
		{
			if(isset($tmp[$item['pid']]))
			{
				if ($tmp[$item['id']]['type'] == 'view') 
				{
					$tmp[$item['id']]['url'] = urlencode($tmp[$item['id']]['code']);
				}
				else
				{
					$tmp[$item['id']]['key'] = $tmp[$item['id']]['code'];
				}
				$tmp[$item['pid']]['sub_button'][] = &$tmp[$item['id']];
			}
			else
			{
				unset($tmp[$item['id']]['type']);
				$tree[] = &$tmp[$item['id']];
			}
			unset($tmp[$item['id']]['id']);
			unset($tmp[$item['id']]['pid']);
			unset($tmp[$item['id']]['code']);
		}
		return $tree;
	}
}