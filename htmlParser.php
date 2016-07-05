<?php

/*
 * This file is part of Class htmlParser.
 *
 * (c) NeneNeko <nene.neko@msm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Neko;

use RuntimeException;
use DOMDocument;
use DOMXPath;

class htmlParser
    {

    /** @var Version htmlParser */
    const version = '1.0.0';

    /** @var Represents an entire HTML or XML document */
    private $domdocument;

    /** @var DOMXPath object */
    private $domobject;

    /** @var Node of evaluates the given XPath expression */
    private $domnode;

    /** @var Encoding of the document */
    public $encoding = 'UTF-8';

    /** @var Remove javascript befor load document */
    public $remove_javascripts = false;

    /** @var Remove stylesheet befor load document */
    public $remove_stylesheet = false;

    /**
     * Creates a parser instance.
     */
    public function __construct ()
        {
        $this -> domdocument = new DOMDocument ();
        $this -> domdocument -> recover = true;
        $this -> domdocument -> formatOutput = true;
        $this -> domdocument -> encoding = $this -> encoding;
        }

    /**
     * Parses html, xml document form string
     * 
     *  @param $string The html, xml document to parse
     *  @param $xml xml flag (true or false)
     *  @return this object
     */
    public function load ( $string, $xml = false )
        {
        if ( !$string )
            throw new RuntimeException ( 'Cannot load string.' );
        if ( $this -> remove_javascripts )
	        $string = preg_replace ( '/<script\b[^>]*>(.*?)<\/script>/is', '', $string );
        if ( $this -> remove_stylesheet )
	        $string = preg_replace ( '/<style\b[^>]*>(.*?)<\/style>/is', '', $string );
        libxml_use_internal_errors ( TRUE );
        $string = self::convertEncoding ( $string );
        if ( $xml )
            $this -> domdocument -> loadXML ( $string );
        else
            {
            $this -> domdocument -> preserveWhiteSpace = true;
            $this -> domdocument -> loadHTML ( $string );
            }
        libxml_clear_errors ();
        $this -> domobject = new DOMXPath ( $this -> domdocument );
        return $this;
        }

    /**
     * Parses html, xml document form file
     * 
     *  @param $file path to html, xml document to parse
     *  @param $xml xml flag (true or false)
     *  @return this object
     */
    public function loadFile ( $file, $xml = false )
        {
        if ( !$string = file_get_contents ( $file ) )
            throw new RuntimeException ( 'Cannot open '.$file.' for reading.' );
        $string = file_get_contents ( $file );
        self :: load ( $string, $xml );
        return $this;
        }

    /**
     * Evaluates the given XPath expression
     * 
     *  @param $xpath The XPath expression to execute.
     *  @return this object
     */
    public function query ( $xpath )
        {
        $this -> domnode = $this -> domobject -> query ( $xpath );
        return $this;
        }

    /**
     * The value or text content of this node
     * 
     *  @param $selectNode Select node of value. 
     *  @param $textContent Returns the text content of this node and its descendants.
     *  @return Value of given XPath expression
     */
    public function getValue ( $selectNode = false, $textContent = false )
        {
        if ( !self :: length () )
            return false;
        $value = array ();
        foreach ( $this -> domnode as $node )
            if ( $node -> nodeType == XML_CDATA_SECTION_NODE || $textContent )
                $value [] = $node -> textContent;
            else
                $value [] = $node -> nodeValue;  
        if ( $selectNode !== false )
            return $value [ $selectNode ];
        else
            return $value;
        }

    /**
     * The attribute of this node
     * 
     *  @param $attribute The name of the attribute or an empty string return attribute as array.
     *  @param $selectNode Select node of value.     
     *  @return The value of the attribute, if no attribute return flase.
     */
    public function getAttribute ( $attribute = false, $selectnode = false )
        {
        if ( !self :: length () or !$this -> domnode -> item (0) -> hasAttributes() )
            return false;
        $value = array ();
        foreach ( $this -> domnode as $key => $node )
            foreach ( $node -> attributes as $attr )
                $value [ $key ] [ $attr -> nodeName ] = $attr -> nodeValue;
        if ( $attribute && $selectnode !== false )
            return $value [ $selectnode ] [ $attribute ];
        elseif ( $selectnode !== false )
            return $value [ $selectnode ];
        elseif ( $attribute )
            {
            $attrvalue = array ();
            foreach ( $value as $attr ) {
                $attrvalue [] = $attr [ $attribute ];
            }
            return $attrvalue;
            }
        else
            return $value;
        }

    /**
     * Canonicalize nodes string or save to file
     * 
     *  @param $file Path to write the output to. or an empty string return as string.
     *  @return Returns canonicalized nodes as a string | TRUE on Write file success or FALSE on failure
     */
    public function innerHTML ( $file = false )
        {
        if ( !self :: length () )
            return false;
        $value = null;
        foreach ( $this -> domnode as $node )
            $value .= $node -> C14N ().PHP_EOL;
        if ( $file )
            return ( file_put_contents ( $file, $value ) ) ? true : false;
        else
            return $value;
        }

    /**
     * The range of valid child node indices is 0 to length
     * 
     *  @return The number of nodes in the list
     */
    public function length ()
        {
        return $this -> domnode -> length;
        }

    /**
     * Detect encoding and convert to target encoding
     * 
     *  @param $string The string being encoded.
     *  @return The encoded string.
     */
    function convertEncoding ( $string )
        {
        $current_encoding = mb_detect_encoding ( $string , 'auto' );
        if ( $current_encoding == $this -> encoding )
            return $string;
        else
            return mb_convert_encoding ( $string, $this -> encoding, $current_encoding );
        }

    /**
     * Dumps the internal document into a string or file using HTML, XML formatting
     * 
     *  @param $file path to html, xml document save to file
     *  @param $xml xml flag (true or false)
     *  @return String internal document | TRUE on Write file success or FALSE on failure
     */
    public function save ( $filename = false, $xml = false )
        {
        if ( $xml )
            if ( $filename )
                return ( $this -> domdocument -> save ( $filename ) ) ? true : false;
            else
                return $this -> domdocument -> saveXML ();
        else
            if ( $filename )
                return ( $this -> domdocument -> saveHTMLFile ( $filename ) ) ? true : false;
            else
                return $this -> domdocument -> saveHTML ();
        }

    }