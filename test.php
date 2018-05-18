<?php

/**
 * Test function for the tuple-dictionary class
 */

if ( ! class_exists( "\\TupleDictionary", true ) )
{
	/**
	 * Include the dictionary code
	 */
	require_once __DIR__ . '/tuple-dictionary.php';
}

if ( ! class_exists( "\\SimpleXMLElementToArray", true ) )
{
	/**
	 * Include the dictionary code
	 */
	require_once __DIR__ . '/SimpleXMLElementToArray.php';
}

// Convert a SimpleXML node to an array
$xml = simplexml_load_file( __DIR__ . "/331-equivalentRelationships-testcase.xml" );
$stoa = new SimpleXMLElementToArray( $xml );
$array = $stoa->ToArray( dom_import_simplexml( $xml )->namespaceURI );

// A couple of handy instances
$obj1 = new \stdClass();
$obj2 = new \stdClass();

// A couple of keys
$key1 = array(
	$obj1,
	array( 'tick' => $obj2 ),
	null,
	null,
	"x",
);

$key2 = array(
	$obj2,
	array( $obj1 ),
	"z",
);

// Create the dictionary and add a couple of values
$dict = new \TupleDictionary( 'md5' );
$dict->addValue( $key1, 1 );
$dict->addValue( $key2, 2 );

// Retrieve values for each key by accessing the keys collection
$keys = $dict->getKeys();
foreach ( $keys as $key )
{
	$value = $dict->getValue( $key, 'xx' );
}

// Access values by using the original key variables or recreate
$value = $dict->getValue( $key1, "yy" );

// Recreate the key to show there is nothing special about a key instance
$key2 = array(
	$obj2,
	array( $obj1 ),
	"z",
);
$value = $dict->getValue( $key2 );

// A key containing an array that has any kind of change such as the order
// of the element or, as in this case, a change to an index of an array
// will yield a different key
$key3 = array(
	$obj1,
	array( 'tock' => $obj2 ),
	null,
	null,
	"x",
);
$value = $dict->getValue( $key3, "yy" );

// Delete a key and show the it has been deleted by retrieving the keys once
// again when there will be just one element.

$result = $dict->delete( $key1 );
$keys = $dict->getKeys();

// Also the previously successful call to retrieve the value will fail.
$value = $dict->getValue( $key1, "yy" );
