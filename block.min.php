<?php
/**
 * 为JS和CSS进行合并和压缩处理
 *
 * @author 张立冰 <roast@php.net>
 * @example 
 * {{min type=js}}
 * /tpl/js/jquery-1.2.1.js
 * /tpl/js/common.js
 * {{/min}}
 * 
 * {{min type=css}}
 * /tpl/css/lifeyp.css
 * {{/min}}
 * 
 * $Id: block.min.php 7125 2009-06-23 05:20:37Z libing $
 */

function smarty_block_min($params, $content, &$smarty) 
{
	global $cfg;
	
	// 支持的文件类型
	$types = array(
		'js'	=> '<script type="text/javascript" src="%url%"></script>',
		'css'	=> '<link type="text/css" href="%url%" rel="stylesheet" />',
		'style'	=> '<link type="text/css" href="%url%" rel="stylesheet" />'
	);
	
	//核心JS文件
	$d9_core_js = array('//js/jquery-1.2.6.min.js','//js/jquery.dialog.js','//js/jquery.cookie.js','//js/common.js','//js/jquery.suggest.js','//js/chat.js');
	$g_core_js = array('//js/jquery-1.2.6.min.js','//js/jquery.dialog.js','//js/jquery.cookie.js','//js/common.js','//js/jquery.suggest.js');
	
	$file = array();
	$lines = explode("\n" , $content);
	foreach ($lines as $key => $val) 
	{
		$val = trim($val);
		
		if (empty($val)) 
			unset($val);
		else 
			$file[] = '//' . $val;
	}

	//其它非法调用
	if (!isset($file[0]) && $params['base'] != '1')
		return ;
		
	//全局定义测试
	//$params['test'] = '1';
		
	/* 将基础JS文件加入到数组头部并标记状态 */
	if ($params['type'] == 'js' && ($cfg['min_js_flag'] !== true || $params['base'] == '1'))
	{
		$cfg['min_js_flag'] = true;	
		
		//公会与没有登录的情况下不需要加载聊天
		if ($_SERVER['HTTP_HOST'] == 'gh.the9.com' || empty($_SESSION['uid'])) 
		{
			if ($params['test'] == '1')
				$file = array_merge($g_core_js, $file);
			
			$js_base_min = "\n<script type=\"text/javascript\" src=\"" . $cfg['url']['root'] . "public/min/gcore.js\"></script>\n";
		}
		else 
		{
			if ($params['test'] == '1')
				$file = array_merge($d9_core_js, $file);
				
			$js_base_min = "\n<script type=\"text/javascript\" src=\"" . $cfg['url']['root'] . "public/min/d9core.js\"></script>\n";
		}
	}
	
	/*标记CSS状态*/
	if (($params['type'] == 'css' || $params['type'] == 'style')  && $cfg['min_css_flag'] !== true)
		$cfg['min_css_flag'] = true;
				
	//如果是测试情况
	if ($params['test'] == '1')	//全部设置为测试状态
	{		
		$data = '';
		foreach ($file as $line)
		{
			$data .= str_replace('%url%', $cfg['url'][$params['type']] . substr($line, (strlen($params['type']) + 3)), $types[$params['type']]) . "\n";
		}

		return $data;
	}
	
	if (isset($file[0]))
	{
		//压缩与合并策略执行
		$hash = abs(crc32(implode('', $file) . MIN_VERSION));
		$params['type'] = ($params['type'] == 'js') ? 'js' : 'css';
	
		if (!file_exists($cfg['path']['temp'] . 'minstat/' . $hash))
		{
			if (!is_dir($cfg['path']['temp'] . 'minstat'))
				mkdir($cfg['path']['temp'] . 'minstat');
			
			touch($cfg['path']['temp'] . 'minstat/' . $hash);
					
			$group_file = $cfg['path']['root'] . 'public/min/groupsConfig.php';
				
			$cache = (file_exists($group_file)) ? include($group_file) : array();
			$cache[$hash . '_' . $params['type']] = $file;
			
			//检查是否有了共用JS
			if (empty($cache['d9core_js']) && $params['type'] == 'js')
				$cache['d9core_js'] = $d9_core_js;
				
			if (empty($cache['gcore_js']) && $params['type'] == 'js')
				$cache['gcore_js'] = $g_core_js;
				
			file_put_contents($group_file, "<?php\n return " . var_export($cache, true) . ';');
		}
		
		return $js_base_min . str_replace('%url%', $cfg['url']['root'] . 'public/min/' . $hash . '.' . $params['type'], $types[$params['type']]) . "\n";
	}
	else 
		return $js_base_min;
}

?>
