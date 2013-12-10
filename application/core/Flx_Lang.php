<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Language Class
 *
 * @package     CodeIgniter
 * @subpackage  Libraries
 * @category    Language
 * @author      ExpressionEngine Dev Team
 * @link        http://codeigniter.com/user_guide/libraries/language.html
 */
class Flx_Lang {

    /**
     * List of translations
     *
     * @var array
     */
    private $language   = array();
    /**
     * List of loaded language files
     *
     * @var array
     */
    var $is_loaded  = array();

    /**
     * Constructor
     *
     * @access  public
     */
    function __construct()
    {
        log_message('debug', "Language Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Load a language file
     *
     * @access  public
     * @param   mixed   the name of the language file to be loaded. Can be an array
     * @param   string  the language (english, etc.)
     * @param   bool    return loaded array of translations
     * @param   bool    add suffix to $langfile
     * @param   string  alternative path to look for language file
     * @return  mixed
     */
    function load($langfile = '', $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '')
    {
        $langfile = str_replace('.php', '', $langfile);

        if ($add_suffix == TRUE)
        {
            $langfile = str_replace('_lang.', '', $langfile).'_lang';
        }

        $langfile .= '.php';

        if (isset($this->is_loaded[$idiom]) && in_array($langfile, $this->is_loaded, TRUE))
        {
            return;
        }

        $config =& get_config();

        if ($idiom == '')
        {
            $deft_lang = ( ! isset($config['language'])) ? 'en' : $config['language'];
            $idiom = ($deft_lang == '') ? 'en' : $deft_lang;
        }

        // Determine where the language file is and load it
        if ($alt_path != '' && file_exists($alt_path.'language/'.$idiom.'/'.$langfile))
        {
            include($alt_path.'language/'.$idiom.'/'.$langfile);
        }
        else
        {
            $found = FALSE;

            foreach (get_instance()->load->get_package_paths(TRUE) as $package_path)
            {
                if (file_exists($package_path.'language/'.$idiom.'/'.$langfile))
                {
                    include($package_path.'language/'.$idiom.'/'.$langfile);
                    $found = TRUE;
                    break;
                }
            }

            if ($found !== TRUE)
            {
                show_error('Unable to load the requested language file: language/'.$idiom.'/'.$langfile);
            }
        }


        if ( ! isset($lang))
        {
            log_message('error', 'Language file contains no data: language/'.$idiom.'/'.$langfile);
            return;
        }

        if ($return == TRUE)
        {
            return $lang;
        }

        $this->is_loaded[$idiom][] = $langfile;
       
        if (array_key_exists($idiom, $this->language))
          $this->language[$idiom] = array_merge($this->language[$idiom], $lang);
        else
          $this->language[$idiom] = $lang;
        unset($lang);

        log_message('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile);
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch a single line of text from the language array
     *
     * @access  public
     * @param   string  $line   the language line
     * @return  string
     */
    function line($line = '', $deft_lang = '')
    {
        if ($deft_lang === '') 
        {
          $config =& get_config();
          $deft_lang = ( ! isset($config['language'])) ? 'en' : $config['language'];
        }
        
        if (!array_key_exists($deft_lang, $this->language)) $deft_lang = 'en';
        
        $value = ($line == '' OR ! isset($this->language[$deft_lang][$line])) ? FALSE : $this->language[$deft_lang][$line];

        // Because killer robots like unicorns!
        if ($value === FALSE)
        {
            log_message('error', 'Could not find the language line "'.$line.'"');
            $value = $line;
        }
        return $value;
    }
    
    function __get($name)
    {
      if ($name === 'language')
      {
        $config =& get_config();
        $deft_lang = ( ! isset($config['language'])) ? 'en' : $config['language'];
        if (!array_key_exists($deft_lang, $this->language)) $deft_lang = 'en';
        return  (isset($this->language[$deft_lang])) ? $this->language[$deft_lang] : FALSE;
      }
    }

}
// END Language Class

/* End of file Lang.php */
/* Location: ./system/core/Lang.php */
