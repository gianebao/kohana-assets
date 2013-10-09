<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Assets Module Helper
 *
 * $Id$
 *
 * @package     Assets Module
 * @author      Alex Sancho, <Original>
 * @author      Gian Carlo Val Ebao, <Maintained_by>
 * @copyright   (c) 2008 Alex Sancho
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. Neither the name of copyright holders nor the names of its
 *    contributors may be used to endorse or promote products derived
 *    from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
 * TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL COPYRIGHT HOLDERS OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

class Assets_Core
{
    /**
     * source folder
     */
    public static $folder = 'media';
    
    /**
     * dump folder
     */
    public static $dump = 'assets';
    
    public static $product = null;
    
	/**
	 * Fetches and parses the configuration file
	 *
	 * @param string $id      name of the configuration group
	 * @param string $device  type of device
	 * @return string
	 * @access public
	 * 
	 */
	public static function parse_config($id, $device)
	{
        $config = Kohana::$config->load($id)->as_array();
        $config = $config[$device];
        
        $types = array('css', 'js');
        $response = array();
        
        foreach ($config as $item)
        {
            $item_type = array_shift($item);
            
            foreach ($types as $type)
            {
                if ($item_type == $type)
                {
                    if (empty($response[$type]))
                    {
                        $response[$type] = array();
                    }
                    
                    $item = array_shift($item);
                    
                    if (Assets::is_internal($item))
                    {
                        $item = '/' . self::$folder . '/' . $item;
                    }
                    
                    $response[$type][] = $item;
                }
            }
        }
        
        return $response;
	}
    
    private static function is_internal($item)
    {
        return 0 !== strpos($item, '//') && 0 !== strpos($item, 'http://') && 0 !== strpos($item, 'https://');
    }

    
	public static function explode($assets, $type)
	{
        $asset = '';
        $render = array(
            'css' => '<link href=":file" rel="stylesheet">',
            'js'  => '<script type="text/javascript" src=":file"></script>'
        );
        
        foreach ($assets[$type] as $item)
        {
            $asset .= strtr($render[$type], array(':file' => $item));
        }
        
        return $asset;
	}
    
	/**
	 * stylesheet
	 * Creates a stylesheet link.
	 *
	 * @param string $style filename
	 * @param string $media media type of stylesheet
	 * @param boolean $index include the index_page in the link
	 * @return string An HTML stylesheet link.
	 * @access public
	 * 
	 */
	public static function stylesheet($style, $output = null, $media = FALSE, $index = FALSE)
	{
        // return html::link(self::glue($style, '.css'), 'stylesheet', 'text/css', 'screen', $media, $index);
		return html::style(Assets::$dump . '/' . self::glue($style, '.css', $output), array('media' => 'screen'));
	}

	/**
	 * script
	 * Creates a script link.
	 *
	 * @param string $script filename
	 * @param string $index include the index_page in the link
	 * @return string An HTML script link.
	 * @access public
	 * 
	 */
	public static function script($script, $output = null, $index = FALSE)
	{
        return html::script(Assets::$dump . '/' . self::glue($script, '.js', $output));
	}

	/**
	 * glue
	 * Takes an array of files and creates a new one containing all contents.
	 * Returns the name of created file
	 * 
	 * @param array $files
	 * @param string $ext
	 * @return string
	 * @access private
	 * 
	 */
	private static function glue($files, $ext, $output = null)
	{
        $local_path = DOCROOT. Assets::$dump . '/';
        $media_path = DOCROOT;

        $js_data = '';

		$files = array_unique((array) $files);

		$files_lastmodified = self::get_last_modified($files, $media_path, $ext);

        if (empty($output))
        {
            $output = md5(implode('', $files));
        }
		$filename = str_replace('.', '', $ext).'/' . $output;

		$filesrc = $local_path.$filename.$ext;

		if (( ! file_exists($filesrc)) OR (filemtime($filesrc) < $files_lastmodified))
		{
			ob_start();

			foreach($files as $script) 
			{
				$suffix = (strpos($script, $ext) === FALSE) ? $ext : '';

                if (self::is_internal($script))
                {
                    $script = $media_path.$script;
                }
            
                if ($ext == '.css')
                {
                    echo self::compress_css(file_get_contents($script));
                }
                else if ($ext == '.js') 
                {
                    $js_data .= file_get_contents($script);
                }
			}

            if ($ext == '.js') 
            {
                echo self::compress_js( $js_data );
            }

			file_put_contents($filesrc, ob_get_clean(), LOCK_EX);
		}

		return $filename.$ext;
	}

	/**
	 * get_last_modified
	 * Takes an array of filenames and returns the most recent modified date
	 * 
	 * @param array $files
	 * @param string $path
	 * @param string $ext
	 * @return int
	 * @access private
	 * 
	 */
	private static function get_last_modified($files, $path, $ext)
	{
		$last_modified = 0;

		foreach($files as $file) 
		{
			$suffix = (strpos($file, $ext) === FALSE) ? $ext : '';
            
            if (!self::is_internal($file))
            {
                continue;
            }
            
            $modified = filemtime($path.$file);
            
			if($modified !== false and $modified > $last_modified) 
				$last_modified = $modified;
		}

		return $last_modified;
	}

  /**
   * compress_js
   *
   * @param string
   * @return string
   *
   */
	private static function compress_js($data)
	{
        if (false === class_exists('JSMin', false))
        {
            require_once Kohana::find_file('vendor', 'jsmin/jsmin');
        }
        
        return JSMin::minify($data);
	}

  /**
   * compress_css
   *
   * @param string
   * @return string
   *
   */
  private static function compress_css($data)
  {
    $data = preg_replace('~/\*[^*]*\*+([^/][^*]*\*+)*/~', '', $data);
    $data = preg_replace('~\s+~', ' ', $data);
    $data = preg_replace('~ *+([{}+>:;,]) *~', '$1', trim($data));
    $data = str_replace(';}', '}', $data);
    $data = preg_replace('~[^{}]++\{\}~', '', $data);
    return $data;
  }

}