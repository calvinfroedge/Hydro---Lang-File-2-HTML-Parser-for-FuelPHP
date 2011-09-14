<?php
namespace Hydro;

class Hydro
{
	/*
	* Considered HTML
	*/
	protected static $_considered_html = '';
	
	/*
	* Treat any descendants as children with nested tag from config
	*/
	protected static $_as_children = '';
	
	/*
	* Called automatically when class is initiated
	*/
	public static function _init()
	{
		\Config::load('hydro', true);
		self::$_considered_html = \Config::get('hydro.considered_html');
		self::$_as_children = \Config::get('hydro.as_children');
	}
	
	/*
	* Utilizes recursion and Fuel's HTML class / HTML helpers for generating content from an array
	*
	* @param	string	The input array
	*/	
	public static function parse($content)
	{
		$return_string = '';
		if(is_array($content))
		{
			foreach($content as $k=>$v)
			{
				//Check to see if the tag has a class or id
				list($tag, $attributes) = self::_check_attributes($k);
						
				//Check to see if the tag is an html element and does not contain spaces, if not make it a div
				if(!in_array($tag, self::$_considered_html) AND !strstr($tag, ' '))
				{
					$attributes = array('class' => $tag);
					$return_string .= html_tag('div', $attributes, self::parse($v));
				}
				//End "tag is html?" check
				
				//Check to see if the tag is a parent tag, like a <ul>
				$not_parent = true;
				if(array_key_exists($tag, self::$_as_children))
				{	
					$not_parent = false;
					if(method_exists('Html', strtolower($tag)))
					{
						$return_string .= \Html::$tag($v, $attributes);
					}
					else
					{
						$return_string .= html_tag($tag, $attributes, $v);
					}
				}
				//End parent tag check
				
				//If the array is numerically indexed and is not a parent tag, array keys will be repeated
				if($not_parent === true AND is_array($v) AND !self::_is_assoc($v))
				{
					foreach($v as $ni_key=>$ni_val)
					{
						$return_string .= html_tag($tag, $attributes, $ni_val);
					}
				}
				
				if(is_string($v))
				{
					$return_string .= html_tag($tag, $attributes, $v);
				}
			}
		}
		else
		{
			return $content;
		}
		return $return_string;
	}

	/*
	* Checks a key to see if any attributes should be added to the tag
	*
	* @param	string	The key to check
	*/	
	private static function _check_attributes($key)
	{
		if(strstr($key, '.'))
		{
			$exploded = explode('.', $key);
			$tag = $exploded[0];
			$class = $exploded[1];	
			$attributes = array('class' => $class);
		}
		else if(strstr($key, '#'))
		{
			$exploded = explode('#', $key);
			$tag = $exploded[0];
			$id = $exploded[1];	
			$attributes = array('id' => $id);					
		}
		else
		{
			$tag = $key;
			$attributes = array();
		}
		
		return array($tag, $attributes);
	}

	/*
	* Checks whether the array is associative or numerically indexed
	*
	* @param	string	The input array
	*/		
	private static function _is_assoc($arr)
	{
	    return array_keys($arr) !== range(0, count($arr) - 1);
	}
	
}