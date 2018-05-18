<?php

/**
 * SimpleXMLToArray
 * @author Bill Seddon
 * @version 0.1.1
 * @copyright Lyquidity Solutions Limited 2017
 * @license GPL 3
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */

// This class has a dependency on the lyquidity\xml project
if ( ! class_exists( '\\lyquidity\\xml\\schema\\SchemaTypes', true ) )
{
	$xmlSchemaPath = isset( $_ENV['XML_LIBRARY_PATH'] )
		? $_ENV['XML_LIBRARY_PATH']
		: ( defined( 'XML_LIBRARY_PATH' ) ? XML_LIBRARY_PATH : __DIR__ . "/../xml/" );

	require_once $xmlSchemaPath . '/bootstrap.php';
}

use lyquidity\xml\schema\SchemaTypes;

/**
 * Implements a wrapper class for SimpleXMLElement so a XML fragment
 * can be converted to an array taking into account namespaces
 */
class SimpleXMLElementToArray
{
	/**
	 * A reference to the global types instance
	 *
	 * @var XBRL_Types $types
	 */
	private $types;

	/**
	 * The node to be processed
	 *
	 * @var SimpleXMLElement
	 */
	private $xml;

	/**
	 * Constructor
	 *
	 * @param SimpleXMLElement $xml
	 */
	function __construct( $xml )
	{
		// Get the existing schema types instance
		$this->types = SchemaTypes::getInstance();
		$this->xml = $xml;
	}

	/**
	 * Convert the SimpleXMLElement instance to an array.
	 * This will generate an array from the current node not the whole document
	 *
	 * Attributes will appear as named array elements
	 * The 'name' and 'id' properties are reserved for these XML identifiers
	 * 'prefix' and 'type' are also reserved
	 * Sub-elements will appear in a 'children' element
	 *
	 * @param string $currentNamespace SimpleXMLElementToArray
	 * @param bool $supportRepeatingElements (default = true)
	 * @return array
	 */
	public function ToArray( $currentNamespace, $supportRepeatingElements = true )
	{
		$result = $this->convert( $this->xml, $currentNamespace, $supportRepeatingElements );
		return $result;
	}

	/**
	 * Performs the task of generating the array
	 * When $supportRepeatingElements ois true the repeated elements appear with a numeric
	 * sequence number under the ['children'][<element name>] node.  When false the sub
	 * element content will appear directly under the ['children'][<element name>].
	 *
	 * @param SimpleXMLElement $node The XML node to be converted
	 * @param array $namespace The namespace of the current node
	 * @param bool $supportRepeatingElements (default = true)
	 * @return array
	 */
	private function convert( $node, $namespace, $supportRepeatingElements = true, $sequenceNumber = 0 )
	{
		$result = array(
			'name' => $node->getName(),
			'value' => trim( (string) $node ),
			'sequenceNumber' => $sequenceNumber,
		);

		// The prefix of a type in the global types list is determined by the prefix
		// it has in the taxonomy schema document not the prefix it may have in the
		// instance document.  This makes sure the correct prefix is used.
		$typePrefix = $this->types->getPrefixForNamespace( $namespace );
		$element = $this->types->getElement( $result['name'], $typePrefix );
		if ( $element )
		{
			$result['type'] = "$typePrefix:{$result['name']}";
		}

		if ( ! strlen( $result['value'] ) )
		{
			if ( $element && isset( $element['default'] ) )
			{
				$result['value'] = $element['default'];
			}
			else
			{
				unset( $result['value'] );
			}
		}

		// There may be attributes using the default namespace
		$attributes = (array) $node->attributes();
		if ( isset( $attributes['@attributes'] ) )
		{
			$result += $attributes['@attributes'];
		}

		$ordinal = 0;

		foreach ( array( '' => null ) + $this->xml->getDocNamespaces( true ) as $prefix => $namespace )
		{
			// Grab namespace specific attributes
			$attributes = (array) $node->attributes( $namespace );
			if ( isset( $attributes['@attributes'] ) )
			{
				$result += $attributes['@attributes'];
			}

			$children = array();

			foreach ( $node->children( $namespace ) as $name => $child )
			{
				// Build the nodes
				$childNodes = $this->convert( $child, $namespace, $supportRepeatingElements, $ordinal++ );
				$childNodes['prefix'] = $prefix;

				if ( $supportRepeatingElements )
				{
					$children[ $name ][] = $childNodes;
				}
				else
				{
					$children[ $name ] = $childNodes;
				}
			}

			if ( count( $children ) )
			{
				if ( ! isset( $result['children'] ) ) $result['children'] = array();
				$result['children'] += $children; // Maybe this should use the array_merge_recurive function
			}
		}

		return $result;
	}
}