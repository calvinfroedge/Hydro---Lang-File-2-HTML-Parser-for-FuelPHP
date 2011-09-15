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
			if(!is_null($wrapper))
			{
				list($tag, $attributes) = self::_check_attributes($wrapper);
				$return_string .= self::_tag_open($tag, $attributes);
			}
			
			$return_string .= self::_parse_array($content);
			
			if(!is_null($wrapper))
			{
				$return_string .= "</$tag>";
			}
		}
		else if (is_string($content))
		{
			$return_string .= self::_parse_string($content, $wrapper);
		}
		
		return $return_string;
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
			list($tag, $attributes) = self::_check_attributes($k);
			$return_string .= self::_tag_open($tag, $attributes);
			
			if(is_array($v) AND !array_key_exists($tag, self::$_as_children))
			{	
				foreach($v as $child_key=>$child_val)
				{
					$return_string .= self::parse($child_val, $child_key);
				}
					
				$return_string .= "</$tag>";
			}
			else if(is_array($v) AND array_key_exists($tag, self::$_as_children))
			{	
				$child_tag = self::$_as_children[$tag];
					
				foreach($v as $child_key=>$child_val)
				{
					$return_string .= "<$child_tag>".self::parse($child_val)."</$child_tag>";
				}	
				$return_string .= "</$tag>";				
			}

			
			else
			{
				$return_string .= self::parse($v);
				$return_string .= "</$tag>";			
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

		$return_string = '';
		$return_string = "<$tag";
		if(!is_null($attributes))
		{
			foreach ($attributes as $attribute_key=>$attribute_value)
			{
				$return_string .= ' '.$attribute_key.'='.$attribute_value.'';
			}
		}
		$return_string .= ">";	
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
		
		if($key[0] === '.' OR $key[0] === '#')
		{
			$tag = 'div';
			$div = substr($key, 1, strlen($key));			
		}

		if($key[0] === '.')
		{
			$attributes['class'] = $div;
		}
		
		if($key[0] === '#')
		{
			$attributes['id'] = $div;
		}

		if($key[0] === '.' OR $key[0] === '#')
		{
			$key = substr($key, 1, strlen($key));		
		}				

		if(strstr($key, '.'))
		{
			if(strstr($key, ' '))
			{
				$sep_by_space = explode(' ', $key);
				$key_to_split = $sep_by_space[0];
				unset($sep_by_space[0]);
				foreach($sep_by_space as $sep_by_space_explode)
				{
					list($attr_key, $attr_value) = explode('=', $sep_by_space_explode);
					$attributes[$attr_key] = $attr_value;
				}
			}	
			else
			{
				$key_to_split = $key;
			}				
			list($tag, $class) = explode('.', $key_to_split);
			$attributes['class'] = $class;
		}
		
		if(strstr($key, '#'))
		{
			if(strstr($key, ' '))
			{
				$sep_by_space = explode(' ', $key);
				$key_to_split = $sep_by_space[0];
				unset($sep_by_space[0]);
				foreach($sep_by_space as $sep_by_space_explode)
				{
					list($attr_key, $attr_value) = explode('=', $sep_by_space_explode);
					$attributes[$attr_key] = $attr_value;
				}				
			}
			else
			{
				$key_to_split = $key;
			}
			if(substr_count($key_to_split, '#')  > 0)
			{
				list($tag, $id) = explode('#', $key_to_split);	
				$attributes['id'] = $id;
			}			
		}	

		if(!isset($tag))
		{
			$tag = $key;
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
