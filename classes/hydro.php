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
	public static function parse($content, $wrapper = null)
	{
		$return_string = '';
		if(is_array($content))
		{
			$return_string .= self::_parse_array($content, $wrapper);

		}
		else if (is_string($content))
		{
			$return_string .= self::_parse_string($content, $wrapper);
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
		$attributes = array();
		
		if(strstr($key, ' ') === FALSE)
		{
			if(strstr($key, '.'))
			{
				$exploded = explode('.', $key);
				$tag = $exploded[0];
				if (empty($exploded[0])) $tag = 'div';
				$class = $exploded[1];	
				$attributes['class'] = $class;
			}
			else if(strstr($key, '#'))
			{
				$exploded = explode('#', $key);
				$tag = $exploded[0];
				if (empty($exploded[0])) $tag = 'div';
				$id = $exploded[1];	
				$attributes['id'] = $id;					
			}
			else
			{
				$tag = $key;			
			}
		}
		else
		{
			$exploded = explode(' ', $key);
			$tag = $exploded[0];
			unset($exploded[0]);
			foreach($exploded as $k=>$v)
			{
				$attrs = explode(',', $v);
				
				foreach($attrs as $attr)
				{
					$exploded_attr = explode('=', $attr);
					if(isset($exploded_attr[1]))
					{
						$attributes[$exploded_attr[0]] = $exploded_attr[1];
					}
				}
			}
		}

		return array($tag, $attributes);
	}

	/*
	* Parse a string
	*
	* @param	string	The string to parse
	* @return	string	The parsed string
	*/
	private static function _parse_string($content, $wrapper = null)
	{
		$return_string = '';
		
		if(!is_null($wrapper))
		{
			$return_string = html_tag($wrapper, array(), $content);
		}
		else
		{
			$return_string = $content;
		}
			
		return $return_string;	
	}

	/*
	* Parse an array
	*
	* @param	array	The array to parse
	* @return	string	The parsed string
	*/	
	private static function _parse_array($content, $wrapper = null)
	{
		$return_string = '';
		foreach($content as $k=>$v)
		{
			$not_parent = true;
				
			if(!empty($wrapper))
			{
				$return_string .= self::_tag_open($wrapper);
				$return_string .= self::parse($v, $k);
				$return_string .= "</$wrapper>";
			}

			list($tag, $attributes) = self::_check_attributes($k);
				
			if(is_array($v) AND !array_key_exists($tag, self::$_as_children))
			{
				$return_string .= self::_tag_open($tag, $attributes);
					
				foreach($v as $child_key=>$child_val)
				{
					$return_string .= self::parse($child_val, $child_key);
				}
					
				$return_string .= "</$tag>";
			}
			//Check to see if the tag is a parent tag, like a <ul>
			else if(is_array($v) AND array_key_exists($tag, self::$_as_children))
			{	
				$not_parent = false;
				$child_tag = self::$_as_children[$tag];
					
				$return_string .= self::_tag_open($tag, $attributes);
				foreach($v as $child_key=>$child_val)
				{
					$return_string .= "<$child_tag>".self::parse($child_val)."</$child_tag>";
				}	
				$return_string .= "</$tag>";				
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
		}
		return $return_string;	
	}
	
	/*
	* Builds a tag open
	*
	* @param	string	The tag
	* @param	string	The tag attributes
	* @return	string	The built tag
	*/		
	private static function _tag_open($tag, $attributes = null)
	{	
		if ($tag[0] === '.' OR $tag[0] === '#')
		{
			$tag = 'div'.$tag;
		}
		$return_string = '';
		$return_string = "<$tag";
		if(!is_null($attributes))
		{
			foreach ($attributes as $attribute_key=>$attribute_value)
			{
				$return_string .= ' '.$attribute_key.'="'.$attribute_value.'"';
			}
		}
		$return_string .= ">";	
		return $return_string;
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