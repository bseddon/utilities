<?php

/**
 * Implements a PHP stream wrapper class that is able represent a file stream in memory.
 * The stream is registered using the protocol 'mem://'.  Streams are read/write only.
 * Locking is not supported.
 *
 * @author Bill Seddon
 * @version 0.9
 * @copyright Lyquidity Solutions Limited 2018
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

/**
 * Unlike the PHP stream implemenations php://temp and php://memory this implementation
 * allows a file to be written, closed and later re-opened where the contents continues
 * to exist.  The php:// streams do not retain data after the stream is closed.
 *
 * A use case fo this stream is to replace the need for temporary files and the need
 * to manage clean up but where a file name is expected.  For example, you may have a
 * function that expects to be passed a file name.  If you want to pass some data
 * currently in memory its necessary to write it to temporary file, call the function
 * passing the name of the temporary file then clean up after.
 *
 * Instead using this stream wrapper then data can be written to a named 'in-memory' s
 * tream and the name passed to the function.  Clean up can still be performed (to recover
 * memory) but the 'file' will disappear when the PHP application ends.
 *
 * The implementation only provides support for files but it can easily be extended to
 * provided an in-memory file system.
 * There is no support for locking.
 * There is no limit on the memory that might be used to store content
 *
 * Example
 *
 * // Create a pseudo file.  The mode (w+) is ignored but the function requires it.
 * $handle = fopen( "mem://myfile.ext", "w+" );
 * // Write something
 * fwrite( $handle, "Some content" );
 * // 'close' the file
 * fclose( $handle );
 *
 * // Access the content using another function that supports stream wrappers
 * $content = file_get_contents("mem://myfile.ext");
 *
 * // Unlink frees any memory but will happen when the script ends anyway
 * unlink( "mem://myfile.ext" );
 */
class MemoryStream
{
	public static function tests()
	{
		// Open creates the file.  Mode (w+) is ignored as the stream is alwaysread/write.
		$f = fopen( "mem://a", 'w+' );

		// Write 'abc' to the stream
		fwrite( $f, "abc" );

		// Close but the contents should not be lost
		fclose( $f );

		// Open the same file to access the contents once again
		$f = fopen( "mem://a", 'w+' );

		// Move the pointer one step forward
		fseek( $f, 1 );

		// Read up to 3 chars.  Only two should be returned
		$s = fread( $f, 3 );

		// Move to the beginning
		rewind( $f );

		// Read up to 3 chars.  All three chars should be returned
		$s = fread( $f, 3 );

		// Move to the end
		fseek( $f, 0, SEEK_END );

		// 'xyz' should be added to the end
		fwrite( $f, 'xyz' );

		// Get the current position
		$pos = ftell( $f );

		// Should return true
		$end = feof( $f );

		// Move to the third position
		fseek( $f, 3 );

		// After this should now be 'abcdefxyz'
		fwrite( $f, 'def' );

		// Get fie information
		$x = fstat( $f );

		// Close the stream
		fclose( $f );

		// Use a different, stream aware function to access the contrnt
		$x = file_get_contents("mem://a");

		// Is the stream a directory?
		$dir = is_dir( 'mem://a' );

		// Is the stream a directory?
		$file = is_file( 'mem://b' );

		// Remove the stream and lose the contents
		unlink( 'mem://a' );

	}

	/**
	 * A catalog of opened and undeleted files
	 * @var array
	 */
	private static $files = array();

	/**
	 * Filename of the current instance.  The instance is represented by a handle.
	 * @var string
	 */
	private $filename;

	/**
	 * Uses the $path 'host' component as the name of the psuedo file.
	 * @param string $path This wil be of the form mem://x.y where 'mem' is the name registered as the wrapper protocol
	 * @param string $mode Ignored
	 * @param int $options Ignored
	 * @param string $opened_path Ignored
	 * @return boolean
	 */
	public function stream_open( $path, $mode, $options, &$opened_path )
	{
		$url = parse_url( $path );
		$this->filename = $url["host"];
		if ( ! isset( self::$files[ $this->filename ] ) )
		{
			self::$files[ $this->filename ] = array( 'buffer' => '', 'pos' => 0, 'atime' => time(), 'mtime' => time(), 'ctime' => time() );
		}
		// $this->file = &self::$files[ $this->filename ];
		self::$files[ $this->filename ]['pos'] = 0;
		return true;
	}

	/**
	 * Reads $count characters from the stream starting at the current position
	 * @param int $count
	 * @return string
	 */
	public function stream_read( $count )
	{
		self::$files[ $this->filename ]['atime'] = time();

		$p = &self::$files[ $this->filename ]['pos'];
		$ret = substr( self::$files[ $this->filename ]['buffer'], $p, $count );
		$p += strlen( $ret );
		return $ret;
	}

	/**
	 * Writes $data to the stream a the current position
	 * @param mixed $data
	 * @return number
	 */
	public function stream_write( $data )
	{
		self::$files[ $this->filename ]['mtime'] = time();

		$v = &self::$files[ $this->filename ]['buffer'];
		$l = strlen( $data );
		$p = self::$files[ $this->filename ]['pos'];
		$v = substr( $v, 0, $p ) . $data . substr( $v, $p );
		self::$files[ $this->filename ]['pos'] += $l;
		return $l;
    }

    /**
     * Returns the current position
     * @return unknown
     */
	public function stream_tell()
	{
		return self::$files[ $this->filename ]['pos'];
	}

	/**
	 * Returns true if the current position is at the end of the stream
	 * @return boolean
	 */
	public function stream_eof()
	{
		$result = self::$files[ $this->filename ]['pos'] >= strlen( self::$files[ $this->filename ]['buffer'] );
		return $result;
	}

	/**
	 * Moves the current position to $offset modified by $whence
	 * @param int $offset
	 * @param int $whence	SEEK_CUR The new position is $offset chars from the current position
	 * 						SEEK_END The new position is the end of the stream plus $offset chars
	 * 						SEEK_SET (default) $offset becomes the new position
	 * @return boolean
	 */
	public function stream_seek( $offset, $whence )
	{
		self::$files[ $this->filename ]['atime'] = time();

		$l = strlen( self::$files[ $this->filename ]['buffer'] );
		$p = &self::$files[ $this->filename ]['pos'];
		switch ( $whence )
		{
			case SEEK_SET: $newPos = $offset; break;
			case SEEK_CUR: $newPos = $p + $offset; break;
			case SEEK_END: $newPos = $l + $offset; break;
			default: return false;
		}
		$ret = ( $newPos >= 0 && $newPos <= $l );
		if ( $ret ) $p = $newPos;
		return $ret;
	}

	/**
	 * Returns standard file information
	 * @return mixed[]
	 */
	public function stream_stat()
	{
		return array(
			'dev' => 1,
			'ino' => 0,
			'mode' => 33206, // 100 666
			'nlink' => 1,
			'uid' => 0,
			'gid' => 0,
			'rdev' => 2,
			'size' => strlen( self::$files[ $this->filename ]['buffer'] ),
			'atime' => self::$files[ $this->filename ]['atime'],
			'mtime' => self::$files[ $this->filename ]['mtime'],
			'ctime' => self::$files[ $this->filename ]['mtime'],
			'blksize' => -1,
			'blocks' => -1,
			0 => 2,
			1 => 0,
			2 => 33206,
			3 => 1,
			4 => 0,
			5 => 0,
			6 => 2,
			7 => strlen( self::$files[ $this->filename ]['buffer'] ),
			8 => self::$files[ $this->filename ]['atime'],
			9 => self::$files[ $this->filename ]['mtime'],
			10 => self::$files[ $this->filename ]['mtime'],
			11 => -1,
			12 => -1
		);
	}

	/**
	 * Flushes any outstanding writes before close.  Does nothing in this implementation.
	 */
	public function stream_flush()
	{
		// Does nothing
	}

	/**
	 * Truncates the content if smaller
	 * @param int $newSize
	 */
	public function stream_truncate( $newSize )
	{
		if ( strlen( self::$files[ $this->filename ]['buffer'] ) >= $newSize ) return true;
		self::$files[ $this->filename ]['buffer'] = substr( self::$files[ $this->filename ]['buffer'], 0, $newSize );
		// If the current position is beyond the new size then adjust the position
		$this->stream_seek( 0, SEEK_END );
		return true;
	}

	/**
	 * Closes the stream but does not lose existing content
	 */
	public function stream_close()
	{
		self::$files[ $this->filename ]['atime'] = time();
		$this->filename = null;
	}

	/**
	 * Closes the stream and removes content
	 * @param string $data
	 */
	public function unlink( $data )
	{
		$url = parse_url( $data );
		$filename = $url["host"];
		$this->filename = null;
		if ( ! isset( self::$files[ $filename ] ) ) return;
		unset( self::$files[ $filename ] );
	}

	public function url_stat( string $path, int $flags )
	{
		$reportErrors = ! ( $flags & STREAM_URL_STAT_QUIET );

		$newFilename = parse_url( $path, PHP_URL_HOST );
		if ( ! isset( self::$files[ $newFilename ] ) )
		{
			if ( $reportErrors )
			{
				trigger_error( "The file '$path' does not exist.", E_WARNING );
			}
			return array();
		}
		$oldFilename = $this->filename;
		$this->filename = $newFilename;
		$stat = $this->stream_stat();
		$this->filename = $oldFilename;
		return $stat;
	}
}

stream_wrapper_register("mem", "MemoryStream")
or die("Failed to register mem:// protocol");
