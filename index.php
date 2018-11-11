<?php
/**
 * EC01 XML Reader
 *
 * Reads an XML file in the directory in which it is placed and displays it as
 * valid HTML. Can also be used as a WordPress plugin.
 *
 * @package Earth3300\EC01
 * @version 0.0.3
 * @author Clarence J. Bos <cbos@tnoep.ca>
 * @copyright Copyright (c) 2018, Clarence J. Bos
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL v3.0
 * @link https://github.com/earth3300/ec01-xml-reader
 *
 * @wordpress-plugin
 * Plugin Name: EC01 XML Reader
 * Plugin URI:  https://github.com/earth3300/ec01-xml-reader
 * Description: Reads and XML file and displays it in HTML.  Shortcode [xml-reader dir=""].
 * Version: 0.0.3
 * Author: Clarence J. Bos
 * Author URI: https://github.com/earth3300/
 * Text Domain: ec01-xml-reader
 * License:  GPL v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * Standards: https://semver.org/  Versioning
 * Standards: https://www.php-fig.org/psr/  PHP Formatting
 * Standards: http://docs.phpdoc.org/references/phpdoc/tags/  Documentation.
 *
 * Standards: https://www.w3.org/standards/xml/  XML
 *
 * File: index.php
 * Created: 2018-10-07
 * Updated: 2018-11-11
 * Time: 10:12 EST
 */

namespace Earth3300\EC01;

/**
* Reads an XML file and displays it in HTML.
 *
 * The environment switch is found at the bottom of this file.
 */
class XMLReader
{

	/** @var array Default options. */
	protected $opts = [
		'max_files' => 1,
		'max_length' => 1000*10,
    'type' => 'xml',
    'ext' => '.xml',
		'dir' => '/data',
		'index' => false,
		'file' => 'weather.xml',
		'title' => 'XML Reader',
    'css' => '/0/theme/css/style.css',
		'url' => 'https://github.com/earth3300/ec01-xml-reader',
		'msg' => [
			'success' => 'Success',
			'not_available' => 'Not Available',
			'error' => 'Error',
		],
	];

	/**
	 * Gets XML File and Return as HTML
	 *
	 * Allow only XML. Possibly use the MIME type.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function get( $args = null )
	{
		/** Figure out what is happening, set the switches accordingly. */
		$args = $this->setTheSwitches( $args );

		/** Set the page class to the type. */
		$args['class'] = $this->opts['type'];

    /** Get the name of the containing directory. */
    $file['dir'] = basename(__DIR__);

    /** Construct the file name out of the file directory and its extension. */
    $file['name'] = $file['dir'] . $this->opts['ext'];

    /** Get the root path for the file (sever root to site root). */
    $file['root'] = $this->getSitePath();

    /** Get the file path (root to directory file is in). */
    $file['path'] = $this->getFilePath( $args );

    /** Get the base path of the file, including the file name. (Needs name, path, root). */
    $file['base'] = $this->getFileBase( $file );

    /** The file path, plus the name of the file. */
    $file['patf'] = $file['path'] . '/' . $file['name'];

    /** Get the base path of the file, including the file name. */
    $file['src'] = $this->getFileSrc( $file );

    /** Get the item XML. */
    $file['xml'] = $this->getItemXML( $file );

    /** Get the item array from XML */
    $file['data'] = $this->xmlObjToArr( $file );

    /** Open the article element. */
    $file['html'] = '<article>' . PHP_EOL;

    /** Get the item HTML. Note the dot preceding the equals sign. */
    $file['html'] .= $this->getItemHtml( $file );

		/** Close the article element. Note the dot preceding the equals sign. */
		$file['html'] .= '</article>' . PHP_EOL;

		/** If the request is for a full page, wrap the HTML in page HTML. */
		if ( isset( $args['doctype'] ) && $args['doctype'] )
		{
			/** Note the lack of a preceding '.' before the equals sign. Important!!! */
			$str = $this->getPageHtml( $file, $args );
		}

		/** Deliver the HTML, wrapped in page HTML or not. */
		return $str;
	}

	/**
	 * Get the source for the file, for use in an image element, for example.
	 *
	 * @param array $file
	 *
	 * @return string
	 */
	private function getFileSrc( $file )
	{
		/** Remove the part of the path that is before the site root. */
		$src = $file['base'] . '/' . $file['name'];

		/** Return $src. */
		return $src;
	}

  /**
	 * Get the File Base (Site Root to File Directory)
	 *
	 * @param array $file
	 *
	 * @return string
	 */
	private function getFileBase( $file )
	{
		/** Remove the file name from the full path. */
		$base = str_replace( $file['name'], '', $file['path'] );

    /** Remove the root path from the full base. */
    $base = str_replace( $file['root'], '', $base );

    /** Just in case, remove the preceding slash, and add it again. */
		$base = '/' . ltrim( $base, '/' );

    /** Remove the following slash, if it exists */
    $base = rtrim( $base, '/' );

		/** Return the base path. */
		return $base;
	}

	/**
	 * Get the SITE_PATH
	 *
	 * Get the SITE_PATH from the constant, from ABSPATH (if loading within WordPress
	 * as a plugin), else from the $_SERVER['DOCUMENT_ROOT']
	 *
	 * Both of these have been tested online to have a preceding forward slash.
	 * Therefore do not add one later.
	 *
	 * @return string
	 */
	private function getSitePath()
	{
		if ( defined( 'SITE_PATH' ) )
		{
			return SITE_PATH;
		}
		/** Available if loading within WordPress as a plugin. */
		elseif( defined( 'ABSPATH' ) )
		{
			return ABSPATH;
		}
		else
		{
			return $_SERVER['DOCUMENT_ROOT'];
		}
	}

  /**
   * Get the File Path
   *
   * @param array $file
   * @param array $args
   *
   * @return string
   */
   private function getFilePath( $args )
   {
     if ( $args['self'] )
     {
       /** The path is the path to this directory. */
       $path = __DIR__;
     }
     else
     {
       /** The 'dir' is from the base of the site. */
       $path = $this->getSitePath() . '/' . $args['dir'];
     }

     return $path;
   }

	/**
	 * Get the Base Path to the Media Directory.
	 *
	 * This does not need to include the `/media` directory.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	private function getBasePath( $args )
	{
		if ( isset( $args['self'] ) )
		{
			$path = __DIR__;
		}
		elseif ( defined( 'SITE_CDN_PATH' ) )
		{
			$path = SITE_CDN_PATH;
		}
		/** Assume the current directory if no other directives. */
		else
		{
			$path = __DIR__;
		}
		return $path;
	}

	/**
	 * Get the Working Directory
	 *
	 * @param array $args
	 *
	 * @return string
	 *
	 * @example $args['dir'] = '/my/directory/'
	 */
	private function getWorkingDir( $args )
	{
		if ( isset( $args['dir'] ) )
		{
			$dir = $args['dir'];
		}
		else
		{
			$dir = $this->opts['dir'];
		}
		return $dir;
	}

  /**
   * Get Item XML

   * Load the file as an XML object, if it exists and perform some basic checks.
	 *
	 * @param array $file  Contains $file['path']
	 *
	 * @return object|false|null
	 */
	private function getItemXML( $file )
	{
    /** Initialize the $xml object to null. */
    $xml = null;

  	/** Check if the file exists. */
		if ( file_exists( $file['path'] ) )
		{
      /** Load the well formed XML file as an XML object.  */
      $xml = simplexml_load_file( $file['path'] );
      /** If $xml evaluates to false, set it explicitly to false. */
      if ( $xml === false )
      {
        $xml = false;
      }
    }
    /** Return $xml ( object|false|null ). */
    return $xml;
  }

	/**
	 * Get the Item HTML (From an Array)
	 *
	 *  string file_get_contents (
	 *  	string $filename [,
	 *  	bool $use_include_path = FALSE [,
	 *  	resource $context [,
	 *  	int $offset = 0 [,
	 *  	int $maxlen ]]]]
	 *  	)
	 *
	 * file_get_contents( $file, true, null, 0, $maxlen );
	 *
	 * This function is similar to file(),
	 * except that file_get_contents() returns the file in a string,
	 * starting at the specified offset up to maxlen bytes.
	 * On failure, file_get_contents() will return FALSE.
	 * file_get_contents() is the preferred way to read the contents of a file
	 * into a string. It will use memory mapping techniques if supported by your
	 * OS to enhance performance.
	 *
	 * @param array $args
	 *
	 * @return string|bool
	 */
	private function getItemHTML( $file )
	{
    $html = '';

    $rows = [];

    foreach ( $file['data'] as $row )
    {
        $cells = array();

        foreach ($row as $cell)
        {
            $cells[] = "<td>{$cell}</td>";
        }

        $rows[] = "<tr>" . implode( '', $cells ) . "</tr>" . PHP_EOL;
    }

    $html = "<table>" . implode( '', $rows ) . "</table>";

    return $html;
  }

	/**
	 * Convert an XML String to an XML Object
	 *
	 * simplexml_load_string (
	 * 		string $data [,
	 * 		string $class_name = "SimpleXMLElement" [,
	 * 		int $options = 0 [,
	 * 		string $ns = "" [,
	 * 		bool $is_prefix = FALSE ]]]]
	 * 		)
	 * @link http://php.net/manual/en/function.simplexml-load-string.php
	 *
	 * @param string $str
	 *
	 * @return object|bool
	 */
	 private function strToXML( $str )
	 {
		 /** Convert a well-formed XML string into an XML object. */
		 $xml = simplexml_load_string( $str, "SimpleXMLElement", LIBXML_NOCDATA );

		 /** Check the returned value. */
		 if ( $xml !== false )
		 {
			 return $xml;
		 }
		 else
		 {
			 return false;
		 }

	 }
	/**
	 * Convert String to XML to JSON to Array
	 *
	 * json_encode (
		 * 	mixed $value [,
		 * 	int $options = 0 [,
		 * 	int $depth = 512 ]]
	 * 	)
	 * @link http://php.net/manual/en/function.json-encode.php
	 *
	 * json_decode (
		 * 	string $json [,
		 * 	bool $assoc = FALSE [,
		 * 	int $depth = 512 [,
		 * 	int $options = 0 ]]]
	 * 	)
	 * 	@link http://php.net/manual/en/function.json-decode.php
	 *
	 *  @param string $str
	 *
	 *  @return array|bool
	 */
	private function getItemArr( $file )
	{
			/** Encode the xml as JSON */
			$json = json_encode( $file['xml'] );

			/** Decode the $json into an array */
			$arr = json_decode( $json, true );

			/** Basic check */
			if ( is_array( $arr ) )
			{
				/** Return the array. */
				return $arr;
			}
			else
			{
				return false;
			}
	}

  /**
   * [xmlObjToArr description]
   *
   * @link http://php.net/manual/en/book.simplexml.php
   *
   * @param  array $file  Array containing an XML Object.
   * @param int $depth Maximum depth to process.
   *
   * @return array  array
   */
  private function xmlObjToArr( $file, $depth = 4 )
  {
    /** Set the object from the array given */
    $obj = $file['xml'];

    if ( is_object( $obj ) )
      {
      /** Initialize the count of the depth to process. */
      $cnt = 0;

      $namespaces = $this->getXMLNamespaces( $obj );

      $namespaces[null] = null;

      $children = [];

      $attr = [];

      $name = strtolower( (string)$obj->getName() );

      $text = trim( (string)$obj );

      if( strlen( $text ) <= 0 )
      {
          $text = null;
      }

      // get info for all namespaces
      if( is_object( $obj ) )
      {
        /** Increment the depth counter. */
        $cnt++;
        if ( $cnt > $depth )
        {
          foreach( $namespaces as $ns => $nsUrl )
          {
            /** Attributes. */
            $objAttr = $obj->attributes( $ns, true );

            foreach( $objAttr as $attrName => $attrValue )
            {
                $attribName = strtolower( trim( (string)$attrName ) );

                $attribVal = trim( (string)$attrValue );

                if ( ! empty( $ns ) )
                {
                    $attribName = $ns . ':' . $attribName;
                }
                $attr[$attribName] = $attribVal;
            }

            /** Children. */
            $objChildren = $obj->children( $ns, true );

            foreach( $objChildren as $childName => $child )
            {
                $childName = strtolower( (string)$childName );

                if( ! empty( $ns ) )
                {
                    $childName = $ns.':'.$childName;
                }
                /** Call this function recursively. */
                $children[ $childName ][] = xmlObjToArr( $child );
            }
          }
        }
      }

      $data = array(
          'name'=>$name,
          'text'=>$text,
          'attr'=>$attr,
          'children'=>$children
      );
      pre_dump( $data );

      return $data;
    }
    else
    {
      return false;
    }
  }

  /**
   * Get the Namespaces in an XML Object
   *
   */
  private function getXMLNamespaces( $obj )
  {
    $arr = null;

    foreach ( $obj as $outer_ns )
    {
      $ns = $outer_ns->getNamespaces( true );

      $child = $outer_ns->children( $ns['p'] );

      foreach ( $child as $out )
      {
          $arr[] =  $out;
      }
    }
    pre_dump( $arr );
    return $arr;
  }

	/**
	 * Set the Switches
	 *
	 * If $args['self'] or $args['dir'] are not set, it assumes we are in the
	 * working directory. Therefore, $args['self'] is set to true and $args['dir']
	 * is set to null. We also have to set the
	 * $args['doctype'] to true to know whether or not to wrap the output in
	 * the correct doctype and the containing html and body elements.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	private function setTheSwitches( $args )
	{
		/** Set the working directory to what is provided, or false.  */
		$args['dir'] = isset( $args['dir'] ) ? $args['dir'] : false;

		/** Set the working directory switch to false. */
		$args['self'] = false;

		/** Set the doctype switch to false. */
		$args['doctype'] = false;

		/** if $args['dir'] == false, set $args['self'] to true. */
		if ( ! $args['dir'] )
		{
			/** Obtain files from the directory in which this file is placed. */
			$args['self'] = true;

			/** Wrap the HTML generated here in page HTML. */
			$args['doctype'] = true;
		}

			/** Return the argument array. */
			return $args;
	}

  /**
	 * Embed the provided HTML in a Valid HTML Page
	 *
	 * Uses the HTML5 DOCTYPE (`<!DOCTYPE html>`), the UTF-8 charset, sets the
	 * initial viewport for mobile devices, disallows robot indexing (by default),
	 * and references a single stylesheet.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public function getPageHtml( $file, $args )
	{
		$str = '<!DOCTYPE html>' . PHP_EOL;
		$str .= sprintf( '<html class="dynamic theme-dark %s" lang="en-CA">%s', $args['class'], PHP_EOL );
		$str .= '<head>' . PHP_EOL;
		$str .= '<meta charset="UTF-8">' . PHP_EOL;
		$str .= '<meta name="viewport" content="width=device-width, initial-scale=1"/>' . PHP_EOL;
		$str .= sprintf( '<title>%s</title>%s', $this->opts['title'], PHP_EOL);
		$str .= $this->opts['index'] ? '' : '<meta name="robots" content="noindex,nofollow" />' . PHP_EOL;
		$str .= sprintf('<link rel=stylesheet href="%s">%s', $this->opts['css'], PHP_EOL);
		$str .= '</head>' . PHP_EOL;
		$str .= '<body>' . PHP_EOL;
		$str .= '<main>' . PHP_EOL;
		$str .= $file['html'];
		$str .= '</main>' . PHP_EOL;
		$str .= '<footer>' . PHP_EOL;
		$str .= '<div class="text-center"><small>';
		$str .= sprintf( 'Note: This page has been <a href="%s">automatically generated</a>. No header, footer, menus or sidebars are available.', $this->opts['url'] );
		$str .= '</small></div>' . PHP_EOL;
		$str .= '</footer>' . PHP_EOL;
		$str .= '</html>' . PHP_EOL;

		return $str;
	}

} // End class

/**
 * Data Class
 *
 * @var [type]
 */
class XMLData extends XMLReader
{
  /**
   *  Station Data
   *
   * @return array
   */
  protected function dataStation()
  {
    $items = [
        'maxtemp' => [ 'id' => 0, 'Max Temp', 'unit'=> '°C', 'load' => 1 ],
        'mintemp' => [ 'id' => 1, 'Min Temp', 'unit'=> '°C', 'load' => 1 ],
        'meantemp' => [ 'id' => 2, 'Mean Temp', 'unit'=> '°C', 'load' => 1 ],
        'heatdegdays' => [ 'id' => 3, 'Heaating Deg Days', 'unit'=> '°C', 'load' => 1 ],
        'cooldegdays' => [ 'id' => 4, 'Cooling Deg Days', 'unit'=> '°C', 'load' => 1 ],
        'totalrain' => [ 'id' => 5, 'Total Rain', 'unit'=> 'mm', 'load' => 1 ],
        'totalsnow' => [ 'id' => 6, 'Total Snow', 'unit'=> 'cm', 'load' => 1 ],
        'totalprecipitation' => [ 'id' => 7, 'Total Precip', 'unit'=> 'mm', 'load' => 1 ],
        'snowonground' => [ 'id' => 8, 'Snow on Ground', 'unit'=> 'cm', 'load' => 1 ],
        'dirofmaxgust' => [ 'id' => 9, 'Max Gust Dir', 'unit'=> '10s Deg', 'load' => 1 ],
        'speedofmaxgust' => [ 'id' => 10, 'Max Gust Speed', 'unit'=> 'km/h', 'load' => 1 ],
      ];
      return $items;
  }

  /**
   * Show Columns
   *
   * Determine which columns to show, based on their numerical key,
   * regardless of what is in that column, or whether or not their
   * contents are known.
   *
   * @return array
   */
  protected function showColumns()
  {
    $items = [
        0 => [ 'load' => 1 ],
        1 => [ 'load' => 1 ],
        2 => [ 'load' => 1 ],
        3 => [ 'load' => 1 ],
        4 => [ 'load' => 1 ],
        5 => [ 'load' => 1 ],
        6 => [ 'load' => 1 ],
        7 => [ 'load' => 1 ],
        8 => [ 'load' => 1 ],
        9 => [ 'load' => 1 ],
        ];
      return $items;
  }
}

/*

<stationdata day="1" month="1" year="2018">
  <maxtemp description="Maximum Temperature" units="°C">-10.9</maxtemp>
  <mintemp description="Minimum Temperature" units="°C">-31.2</mintemp>
  <meantemp description="Mean Temperature" units="°C">-21.1</meantemp>
  <heatdegdays description="Heating Degree Days" units="°C">39.1</heatdegdays>
  <cooldegdays description="Cooling Degree Days" units="°C">0.0</cooldegdays>
  <totalrain description="Total Rain" flag="M" units="mm"/>
  <totalsnow description="Total Snow" flag="M" units="cm"/>
  <totalprecipitation description="Total Precipitation" units="mm">0.0</totalprecipitation>
  <snowonground description="Snow on Ground" units="cm">23</snowonground>
  <dirofmaxgust description="Direction of Maximum Gust" units="10s Deg">26</dirofmaxgust>
  <speedofmaxgust description="Speed of Maximum Gust" units="km/h">37</speedofmaxgust>
</stationdata>

 */

/**
 * Helper Function
 *
 * Call `pre_dump( $arr );` to have array or string outputted and formatted
 * for debugging purposes. A check is done to ensure this function is not
 * called twice.
 */
if ( ! function_exists( 'pre_dump' ) )
{
  function pre_dump( $arr )
  {
    echo "<pre>" . PHP_EOL;
    var_dump( $arr );
    echo "</pre>" . PHP_EOL;
  }
}

/**
 * Callback from the xml-reader Shortcode
 *
 * Performs a check, then instantiates the XMLReader class
 * and returns the xml file found (if any) as HTML.
 *
 * @param array  $args['dir']
 *
 * @return string  HTML as a list of images, wrapped in the article element.
 */
function media_index( $args )
{
	if ( is_array( $args ) )
	{
		$xml_reader = new XMLReader();
		return $xml_reader->get( $args );
	}
	else
	{
		return '<!-- Missing the image directory to process. [xml-reader dir=""]-->';
	}
}

/**
 * Environment Check.
 *
 * In WordPress if a WordPress specific function is found ('add_shortcode' is
 * the one we need, so that is the one a  check is made for).
 */
if( function_exists( 'add_shortcode' ) )
{
	/** No direct access (NDA). */
	defined('ABSPATH') || exit('NDA');

	/** Add shortcode [xml-reader dir=""] */
	add_shortcode( 'xml-reader', 'xml_reader' );
}
/**
 * Else Instantiate the Class Directly (not in WordPress)
 *
 * It could be that this file is within another framework. But as we don't know
 * what that is here, we can't make use of it.
 *
 * @return string
 */
else
{
	$xml_reader = new XMLReader();
	echo $xml_reader->get();
}
