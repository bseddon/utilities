<?php

/**
 * Implements the class TupleDictionary.
 *
 * @author Bill Seddon
 * @version 0.9
 * @copyright Lyquidity Solutions Limited 2018
 * @license GPL 3.0
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
 */

/**
 * Class implementation
 */
class TupleDictionary
{
	/**
	 * Generates hashes for all the elements of the key
	 * @param $elements An array of key elements for which to create the hashes
	 * @return array An array of the elements indexed by their hashes and the overall hash
	 */
	public function hashArray( $elements )
	{
		$hashes = array();

		foreach ( $elements as $key => $element )
		{
			if ( is_object( $element ) )
			{
				$hash = ( method_exists( $element, 'getHash' ) ? $element->getHash() : spl_object_hash( $element ) ) . $key;
				$hashes[ $hash ] = $element;
			}
			else if ( is_array( $element ) )
			{
				extract( $this->hashArray( $element ) ) . $key;
				$hashes[ $hash ] = $element;
			}
			else
			{
				$hash = hash( $this->hash_algorithm, $element . $key );
				$hashes[ $hash ] = $element;
			}
		}

		return array( 'hash' => hash( $this->hash_algorithm, serialize( array_keys( $hashes ) ) ), 'element_hashes' => $hashes );
	}

	/**
	 * The data store
	 * @var array
	 */
	protected $data = array();

	/**
	 * The name of the hashing algorithm to use
	 * @var string
	 */
	protected $hash_algorithm = 'sha256';

	/**
	 * Constructor
	 *
	 * @param string $hash_algorithm The name of the hash algorithm to use and will be 'sha256' by default.
	 *                               Must be one of the name returned by the PHP function hash_algos().
	 * @throws Exception An exception will be thrown if the name of the algorithm is not recognized.
	 */
	function __construct( $hash_algorithm = 'sha256' )
	{
		if ( ! in_array( strtolower( $hash_algorithm ), hash_algos() ) )
			throw new Exception();

		$this->hash_algorithm = $hash_algorithm;
	}

	/**
	 * Add a new node (or replace an existing one) indexed by $key
	 *
	 * @param array $key An array of items to use as an index
	 * @param mixed $value The value to record
	 * @throws \Exception Thrown if no key is provided
	 * @return string The hash for the key
	 */
	public function addValue( $key, $value )
	{
		if ( ! isset( $key ) )
			throw new \Exception( "A valid key has not been provided" );

		if ( ! is_array( $key ) )
			$key = array( $key );

		$result = TupleDictionary::hashArray( $key );

		// $this->data['hashes'][ $result['hash'] ] = $result['element_hashes'];
		$this->data['values'][ $result['hash'] ] = $value;
		$this->data['keys'][ $result['hash'] ] = $key;

		return $result;
	}

	/**
	 * Test whether the $key already exists
	 *
	 * @param array $key An array of items to use as an index
	 * @throws \Exception Thrown if no key is provided
	 * @return bool
	 */
	public function exists( $key )
	{
		if ( ! isset( $key ) )
			throw new \Exception( "A valid key has not been provided" );

		if ( ! is_array( $key ) )
			$key = array( $key );

		$result = TupleDictionary::hashArray( $key );

		return isset( $this->data['values'][ $result['hash'] ] );
	}

	/**
	 * Get a value for for a key
	 *
	 * @param array $key An array of items to use as an index
	 * @param mixed $default
	 * @return string|mixed The value corresponding to the $key or $default if the key is not found
	 */
	public function &getValue( $key, $default = null )
	{
		$result = TupleDictionary::hashArray( $key );

		return $this->getValueByHash( $result['hash'], $default );
	}

	/**
	 * Get a value for a specific hash
	 *
	 * @param string $hash
	 * @param mixed $default
	 * @return $array The value corresponding to the hash
	 */
	public function &getValueByHash( $hash, $default = null )
	{
		if ( ! isset( $this->data['values'][ $hash ] ) )
		{
			return $default;
		}

		return $this->data['values'][ $hash ];
	}

	/**
	 * Get an array of all the keys used
	 */
	public function getKeys()
	{
		return isset( $this->data['keys'] )
			// ? array_values( $this->data['keys'] )
			? $this->data['keys']
			: array();
	}

	/**
	 * Delete a key and any associated value
	 *
	 * @param array $key An array of items to use as an index
	 * @return boolean True if the key exists and the item is deleted or false
	 */
	public function delete( $key )
	{
		$result = TupleDictionary::hashArray( $key );

		if ( ! isset( $this->data['values'][ $result['hash'] ] ) )
			return false;

		unset( $this->data['values'][ $result['hash'] ] );

		if ( ! isset( $this->data['keys'][ $result['hash'] ] ) )
			return false;

		unset( $this->data['keys'][ $result['hash'] ] );
		return true;
	}

	/**
	 * Create an instance from a persisted store
	 * @param string $json
	 * @throws \Exception
	 * @return TupleDictionary
	 */
	public static function fromJSON( $json )
	{
		$array = json_decode( $json, true );
		if ( ! isset( $array['hash_algorithm'] ) || ! isset( $array['data'] ) )
		{
			throw new \Exception("Invalid JSON");
		}

		$dict = new TupleDictionary( $array['hash_algorithm'] );
		$dict->data = $array['data'];

		return $dict;
	}

	/**
	 * Serialize the object as JSON
	 * @return string
	 */
	public function toJSON()
	{
		return json_encode( array( 'hash_algorithm' => $this->hash_algorithm, 'data' => $this->data ) );
	}
}

