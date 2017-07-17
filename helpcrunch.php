<?php
/*
Plugin Name: HelpCrunch Live Chat
Description: A modern live chat, email marketing tool, marketing automation solution and simple CRM in one product.
Version:     1.1.1
*/

/**
 * Class HelpCrunchWPSettingsPage
 */
class HelpCrunchWPSettingsPage
{
    /**
     * @var HelpCrunchWPSettings HelpCrunchWPSettings
     */
    private $settings;

    /**
     * @var string
     */
    private $slug;

    /**
     * HelpCrunchWPSettingsPage constructor.
     * @param HelpCrunchWPSettings $settings
     * @param string $slug
     */
    public function __construct(HelpCrunchWPSettings $settings, $slug)
    {
        $this->settings = $settings;
        $this->slug = $slug;
    }

    /**
     * Make plugin funcs work
     */
    public function registerHooks()
    {
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_menu', array($this, 'addSettingsMenu'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'pluginActionLinks'));
    }

    /**
     * Add default-like settings to a settings page
     */
    public function registerSettings()
    {
        add_settings_section('integration', __('Setup', 'helpcrunch'),
            array($this, 'integrationSection'), $this->slug);
        add_settings_field('api_code', __('HelpCrunch API Code', 'helpcrunch'),
            array($this, 'apiCodeField'), $this->slug, 'integration');

        if (HelpCrunchWP::isTestEnvironment()) {
            add_settings_field('api_domain', __('HelpCrunch Domain', 'helpcrunch'),
                array($this, 'apiDomainField'), $this->slug, 'integration');
        }

        add_settings_section('chat', __('Chat Widget', 'helpcrunch'),
            array($this, 'chatSection'), $this->slug);
        add_settings_field('show_chat_widget', __('Show Chat Widget', 'helpcrunch'),
            array($this, 'showChatWidgetField'), $this->slug, 'chat');

        register_setting(
            $this->slug,
            $this->settings->getOptionName(),
            array($this, 'sanitize')
        );
    }

    /**
     * Add our page into the settings tree in the left menu in admin panel
     */
    public function addSettingsMenu()
    {
        add_options_page(
            'HelpCrunch Settings',
            'HelpCrunch',
            'manage_options',
            $this->slug,
            array($this, 'settingsPage')
        );
    }

    /**
     * @param array $input
     * @return array
     */
    public function sanitize($input)
    {
        $input['show_chat_widget'] = (bool)($input['show_chat_widget']);
        $input['api_code'] = $input['api_code'] ?
            json_decode($input['api_code'], true) : array();
        if (null === $input['api_code']) {
            $input['api_code'] = array();
            add_settings_error(
                $this->settings->getOptionName() . '[api_code]',
                'api_code',
                'Invalid API Code',
                'error'
            );
        }

        return $input;
    }

    /**
     * Shows integration result info
     */
    public function integrationSection()
    {
        if ($this->settings->integrated()) {
            $domain = $this->settings->getOrganization() . '.' . $this->settings->getApiDomain();
            ?>
          <strong>HelpCrunch settings are installed</strong>
          <ul>
            <li>
              You can check your settings string code in "CMS / E-commerce" block at Settings &#8594; Website Widgets
            </li>
            <li>
              If you have any problems with widget - check the
              <a target="_blank" href="https://docs.helpcrunch.com/integrations.html#wordpress-integration">
                integration guide
              </a>
            </li>
            <li>
              If you still have any questions, you can login into your
              <a href="<?php echo HelpCrunchWP::getScheme() ?>://<?php echo esc_attr($domain) ?>" target="_blank">
                HelpCrunch admin account
              </a> and ask us by chat
            </li>
          </ul>
            <?php
        } else {
            ?>
          <strong>You need to enter HelpCrunch API Code</strong>
          <ol>
            <li>
              If you don't have a HelpCrunch account you'll need to register at
              <a href="https://helpcrunch.com/signup.html?utm_medium=helpcrunch&utm_campaign=extensions&utm_source=wordpress_extension" target="_blank">
                https://helpcrunch.com/signup.html
              </a>
              first
            </li>
            <li>
              If you have registered a HelpCrunch account and now you are completing a wizard -
              copy your settings from the first step of the wizard, choosing the <b>WordPress</b>
              from the platforms list
            </li>
            <li>
              If you have skipped a wizard or have already installed a website widget somewhere else -
              go to your <a target="_blank" href="https://helpcrunch.com/signin.html">HelpCrunch admin account</a>,
              then to Settings &#8594; Website Widgets, choose your widget (or create one) and copy a code from
              <b>CMS / E-commerce</b> block in <b>Settings / Setup</b>
            </li>
            <li>
              If you are stuck - check the
              <a target="_blank" href="https://docs.helpcrunch.com/integrations.html#wordpress-integration">
                integration guide
              </a>
              or ask us by chat from your HelpCrunch admin account.
            </li>
          </ol>
            <?php
        }
    }

    /**
     * ?
     */
    public function chatSection()
    {
    }

    /**
     * Shows field for api Code
     */
    public function apiCodeField()
    {
        ?>
      <input name="<?php echo esc_attr($this->settings->getOptionName() . '[api_code]'); ?>"
        class="regular-text code"
        value="<?php if ($this->settings->integrated()) {
            echo esc_attr(json_encode($this->settings->getApiCode()));
        } ?>">
        <?php
    }

    /**
     * Shows field for api Domain
     */
    public function apiDomainField()
    {
        ?>
      <input name="<?php echo esc_attr($this->settings->getOptionName() . '[api_domain]'); ?>"
        class="regular-text domain"
        value="<?php echo esc_attr($this->settings->getApiDomain()); ?>">
        <?php
    }


    /**
     * Show widget checkbox
     */
    public function showChatWidgetField()
    {
        ?>
      <label for="show_chat_widget">
        <input name="<?php echo esc_attr($this->settings->getOptionName() . '[show_chat_widget]'); ?>" type="checkbox" id="show_chat_widget" value="1" <?php checked(true, $this->settings->showChatWidget() ); ?> />
          <?php _e('You can show or hide HelpCrunch widget at any time.', 'helpcrunch'); ?>
      </label>
        <?php
    }

    /**
     * @param array $links
     * @return array
     */
    public function pluginActionLinks($links)
    {
        $links[] = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=' . $this->slug) ) .'">Settings</a>';
        return $links;
    }

    /**
     * Renders the settings page
     */
    public function settingsPage()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Access Denied');
        }
        ?>
      <div class="wrap">
        <h2><?php _e('HelpCrunch Settings', 'helpcrunch'); ?></h2>
        <form method="post" action="options.php">
            <?php
            settings_fields($this->slug);
            do_settings_sections($this->slug);
            submit_button();
            ?>
        </form>
      </div>
        <?php
    }
}

/**
 * Class HelpCrunchWPSettings
 */
class HelpCrunchWPSettings
{
    /**
     * @var string
     */
    private $optionName;

    /**
     * HelpCrunchWPSettings constructor.
     * @param string $optionName
     */
    public function __construct($optionName)
    {
        $this->optionName = $optionName;
    }

    /**
     * Updates option via default WP way
     */
    public function activate()
    {
        $option = $this->getOption();
        if (empty($option)) {
            update_option($this->optionName, $this->getDefaultOptions());
        }
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getOption($key = null)
    {
        $option = get_option($this->optionName);

        return $key ? $option[$key] : $option;
    }

    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return array(
            'api_code' => array(),
            'api_domain' => 'helpcrunch.com',
            'show_chat_widget' => true,
        );
    }

    /**
     * @return string
     */
    public function getOptionName()
    {
        return $this->optionName;
    }

    /**
     * @return string
     */
    public function getApiCode()
    {
        return $this->getOption('api_code');
    }

    /**
     * @return string
     */
    public function getApiDomain()
    {
        $domain = $this->getOption('api_domain');
        return $domain ? $domain : 'helpcrunch.com';
    }

    /**
     * @return string|null
     */
    public function getOrganization()
    {
        $apiCode = $this->getApiCode();

        return isset($apiCode['organization']) ? $apiCode['organization'] : null;
    }

    /**
     * @return bool
     */
    public function showChatWidget()
    {
        return $this->getOption('show_chat_widget');
    }

    /**
     * @return bool
     */
    public function integrated()
    {
        $apiCode = $this->getApiCode();

        return !empty($apiCode);
    }
}

/**
 * Class HelpCrunchWPWidget
 */
class HelpCrunchWPWidget
{
    /**
     * @var HelpCrunchWPSettings
     */
    private $settings;

    /**
     * HelpCrunchWPWidget constructor.
     * @param HelpCrunchWPSettings $settings
     */
    public function __construct(HelpCrunchWPSettings $settings)
    {
        $this->settings = $settings;
    }

    public function registerHooks()
    {
        if ($this->settings->integrated()) {
            add_action('wp_head', array($this, 'addSDK'));
        }
    }

    /**
     * Generates our code
     */
    public function addSDK()
    {
        $apiCode = $this->settings->getApiCode();
        $apiDomain = $this->settings->getApiDomain();

        $init = array(
            'applicationId' => $apiCode['application_id'],
            'applicationSecret' => $apiCode['application_secret']
        );

        $currentUser = wp_get_current_user();
        $userData = array();
        if (!empty($currentUser->id)) {
            $userData['user_id'] = $currentUser->id;
            $userData['signature'] = hash_hmac(
                'sha256',
                $currentUser->id,
                $apiCode['customer_authentication_secret']
            );
        }

        if (!empty($currentUser->user_email)) {
            $userData['email'] = esc_js($currentUser->user_email);
        }
        if (!empty($currentUser->display_name)) {
            $userData['name'] = esc_js($currentUser->display_name);
        }

        if (!empty($userData)) {
            $init['user'] = $userData;
        }

        ?>
      <script type="text/javascript">
        (function(w,d){
          w.HelpCrunch=function(){w.HelpCrunch.q.push(arguments)};w.HelpCrunch.q=[];
          function r(){var s=document.createElement('script');s.async=1;s.type='text/javascript';s.src='<?php echo HelpCrunchWP::getScheme() ?>://widget.<?php echo $apiDomain ?>';(d.body||d.head).appendChild(s);}
          if(w.attachEvent){w.attachEvent('onload',r)}else{w.addEventListener('load',r,false)}
        })(window, document)
      </script>
      <script type="text/javascript">
        HelpCrunch('init', '<?php echo $apiCode['organization'] ?>', <?php echo json_encode($init); ?>);
        <?php
            if ($this->settings->showChatWidget()) {
                ?>HelpCrunch('showChatWidget');<?php
        }
        ?>
      </script>
        <?php
    }
}

/**
 * Class HelpCrunchWP
 */
class HelpCrunchWP
{
    /**
     * @var
     */
    private static $instance;

    /**
     * @var
     */
    private $settings;
    /**
     * @var HelpCrunchWPWidget
     */
    private $widget;

    /**
     * HelpCrunchWP constructor.
     */
    public function __construct()
    {
        $settings = $this->getSettings();
        if (is_admin()){
            $settingsPage = new HelpCrunchWPSettingsPage($settings, 'helpcrunch');
            $settingsPage->registerHooks();
        }
        $this->widget = new HelpCrunchWPWidget($settings);
        $this->widget->registerHooks();
    }

    /**
     * @return HelpCrunchWPSettings
     */
    public function getSettings()
    {
        if (!$this->settings) {
            $this->settings = new HelpCrunchWPSettings('helpcrunch');
        }

        return $this->settings;
    }

    /**
     * @return HelpCrunchWP
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return bool
     */
    public static function isTestEnvironment() {
        return (bool) (strpos($_SERVER['HTTP_HOST'], '.dev') || strpos($_SERVER['HTTP_HOST'], '.stage'));
    }

    /**
     * @return string
     */
    public static function getScheme() {
        return self::isTestEnvironment() ? 'http' : 'https';
    }

    public static function activate()
    {
        self::getInstance()->getSettings()->activate();
    }
}

register_activation_hook(__FILE__, array( 'HelpCrunchWP', 'activate'));
add_action('plugins_loaded', 'HelpCrunchWP::getInstance');
