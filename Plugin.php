<?php
/**
 * Turnstile 后台登录验证
 * 
 * @package TurnstileLogin
 * @version 1.0.0
 * @author 东東
 * @version 1.3.2
 * @link https://dongblog.org/
 */
 
class TurnstileLogin_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        // 拦截登录控制器
        Typecho_Plugin::factory('Widget_Login')->login = array('TurnstileLogin_Plugin', 'verify');
        return _t('插件已启用，请配置 Site Key 和 Secret Key');
    }

    public static function deactivate()
    {
        return _t('插件已禁用');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $siteKey = new Typecho_Widget_Helper_Form_Element_Text(
            'siteKey', NULL, '', 
            _t('Site Key'), 
            _t('Cloudflare Turnstile 的 Site Key（公钥）')
        );
        $form->addInput($siteKey->addRule('required', _t('必须填写 Site Key')));
        
        $secretKey = new Typecho_Widget_Helper_Form_Element_Text(
            'secretKey', NULL, '', 
            _t('Secret Key'), 
            _t('Cloudflare Turnstile 的 Secret Key（私钥，务必保密）')
        );
        $form->addInput($secretKey->addRule('required', _t('必须填写 Secret Key')));
        
        $theme = new Typecho_Widget_Helper_Form_Element_Select(
            'theme', array('light' => '浅色', 'dark' => '深色', 'auto' => '自动'), 'auto',
            _t('主题模式')
        );
        $form->addInput($theme);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 验证逻辑（在密码验证前执行）
     */
    public static function verify($username, $password)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $siteKey = $options->plugin('TurnstileLogin')->siteKey;
        $secretKey = $options->plugin('TurnstileLogin')->secretKey;

        // 未配置时不拦截（防止把自己锁死）
        if (empty($siteKey) || empty($secretKey)) {
            return true;
        }

        $request = Typecho_Request::getInstance();
        $response = Typecho_Response::getInstance();
        
        // 只处理 POST 登录请求
        if (!$request->isPost()) {
            return true;
        }

        $token = $request->get('cf-turnstile-response', '');
        
        if (empty($token)) {
            self::backWithError(_t('请完成人机验证后再登录'));
            return false;
        }

        // 获取真实 IP（兼容 Cloudflare CDN）
        $ip = $request->getServer('HTTP_CF_CONNECTING_IP', 
            $request->getServer('HTTP_X_FORWARDED_FOR', 
                $request->getServer('REMOTE_ADDR')
            )
        );
        // X-Forwarded-For 可能是逗号分隔的列表，取第一个
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        // 调用 Cloudflare 验证 API
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret'   => $secretKey,
                'response' => $token,
                'remoteip' => $ip
            ]),
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $result = json_decode(curl_exec($ch), true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($result['success'])) {
            $errorMsg = !empty($result['error-codes']) 
                ? implode(', ', $result['error-codes']) 
                : 'Unknown error';
            
            // 记录日志便于排查
            error_log('Turnstile verification failed: ' . $errorMsg . ' IP: ' . $ip);
            
            self::backWithError(_t('人机验证失败，请刷新页面重试（错误：%s）', $errorMsg));
            return false;
        }

        return true; // 验证通过，继续 Typecho 原生登录流程
    }

    /**
     * 返回登录页并显示错误
     */
    private static function backWithError($message)
    {
        $notice = Typecho_Widget::widget('Widget_Notice');
        $notice->set($message, 'error');
        
        $request = Typecho_Request::getInstance();
        $referer = $request->getReferer();
        
        // 如果没有 referer，回退到登录页
        if (empty($referer)) {
            $referer = Typecho_Common::url('admin/login.php', Typecho_Widget::widget('Widget_Options')->siteUrl);
        }
        
        Typecho_Response::redirect($referer);
        exit;
    }
}