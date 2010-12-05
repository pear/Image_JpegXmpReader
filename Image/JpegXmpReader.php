<?php

/**
 * JPEG XMP Reader
 *
 * PHP versions >=5
 *
 * LICENSE: This source file is subject to the MIT license as follows:
 * Copyright (c) 2008 P'unk Avenue, LLC
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category  Image
 * @package   Image_JpegXmpReader
 * @author    Tom Boutell <tom@punkave.com>
 * @copyright 2008 P'unk Avenue LLC
 * @license   MIT License
 * @link      http://pear.php.net/package/Image_JpegXmpReader
 * @since     File available since Release 1.0
 */

// /* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Fetch the JpegMarkerReader class
 *
 */

require_once 'PEAR.php';
require_once 'PEAR/Exception.php';
require_once 'Image/JpegMarkerReader.php';

/**
 * Read Photoshop-style XMP metadata from a JPEG file with reasonable efficiency.
 *
 * @category  Image
 * @package   Image_JpegXmpReader
 * @author    Tom Boutell <tom@punkave.com>
 * @copyright 2008 P'unk Avenue LLC
 * @license   MIT License
 * @link      http://pear.php.net/package/Image_JpegXmpReader
 * @since     File available since Release 1.0
 */


class Image_JpegXmpReader extends Image_JpegMarkerReader
{
    /**
      * Creates a new JpegXmpReader object which will read from the
      * specified file
      *
      * @param string $filename file to open
      */
    public function __construct($filename)
    {
        parent::__construct($filename);
    }

    /**
      * Read the next (typically the only) XMP metadata marker in the file
      *
      * On success, returns a SimpleXML object. You don't have to
      * call this function directly if you are not interested in
      * accessing the XML directly. Just call getTitle(), getDescription(), 
      * and so on, which automatically call readXmp if it has not
      * already been called at least once. Calling this function yourself
      * is also a good way to check whether a valid XMP metadata marker
      * is present in the file at all. 
      *
      * If no XMP data is present, returns false (0.5.1, matches the
      * behavior expected by getField). If an unexpected
      * condition occurs, such as failure to open the file or a file
      * which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * Warning: XMP loves namespaces. This function registers the
      * relevant namespaces as an aid to making successful queries
      * against the XMP object, but var_dump may not report anything
      * if called on the XMP object. That's normal. Again, for an easier
      * interface, use the various "get" functions in this class.
      *
      * @return boolean|SimpleXML
      *
      */
    public function readXmp() 
    {
        while (true) {
            $data = $this->readMarker(0xE1);
            if ($data === false) {
                return false;
            }
            $id = "http://ns.adobe.com/xap/1.0/" . sprintf("%c", 0);
            if (substr($data, 0, strlen($id)) !== $id) {
                // Keep looking for another APP1 marker. This will be
                // necessary if a file also has EXIF, for instance.
                continue;
            }
            $data = substr($data, strlen($id));
            break;
        }
        // Ignore the weird nulls and @'s and crap that surround XMP, 
        // extract the juicy XML goodness
        if (preg_match("/(\<x\:xmpmeta.*?\>.*?\<\/x\:xmpmeta\>)/s", 
          $data, $matches)) {
            $data = "<?xml version='1.0'?>\n" . $matches[1];
            $this->_xml = simplexml_load_string($data);
            if ($this->_xml === false) {
                return false;
            }
            $namespaces = $this->_xml->getNamespaces(true);
            foreach ($namespaces as $key => $val) {
                $this->_xml->registerXPathNamespace($key, $val);
            }
            return $this->_xml;
        } else {
            return false;
        }
    }

    /**
      * Retrieve title fields
      *
      * Returns an array consisting of all title fields 
      * found in the XMP metadata (most images only have one,
      * so you may prefer to call getTitle()). 
      *
      * Returns false if no valid XMP metadata markers are present in the file.
      *
      * If an unexpected condition occurs, such as failure to open the 
      * file or a file which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * See also getTitle().
      *
      * @return boolean|array
      *
      */

    public function getTitles()
    {
        return $this->getField('title');
    }

    /**
      * Retrieve title field or fields as a single string
      *
      * Returns a string consisting of all title fields found
      * in the XMP metadata. If more than one is present, they
      * are joined by newlines. If there are no valid XMP
      * metadata markers in the file, this function returns false.
      *
      * If an unexpected condition occurs, such as failure to open the 
      * file or a file which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * See also getTitles().
      *
      * @return boolean|string
      * 
      */

    public function getTitle()
    {
        return $this->getImplodedField('title');
    }

    /**
      * Retrieve description fields
      *
      * Returns an array consisting of all description fields 
      * found in the XMP metadata (most images only have one,
      * so you may prefer to call getTitle()). 
      *
      * Returns false if no valid XMP metadata markers are present in the file.
      *
      * If an unexpected condition occurs, such as failure to open the 
      * file or a file which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * See also getDescription().
      *
      * @return boolean|array
      *
      */

    public function getDescriptions()
    {
        return $this->getField('description');
    }

    /**
      * Retrieve description field or fields as a single string
      *
      * Returns a string consisting of all description fields found
      * in the XMP metadata. If more than one is present, they
      * are joined by newlines. If there are no valid XMP
      * metadata markers in the file, this function returns false.
      *
      * If an unexpected condition occurs, such as failure to open the 
      * file or a file which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * See also getDescriptions().
      *
      * @return boolean|string
      * 
      */

    public function getDescription()
    {
        return $this->getImplodedField('description');
    }

    /**
      * Retrieve subject fields
      *
      * Returns an array consisting of all subject fields 
      * found in the XMP metadata (most images only have one,
      * so you may prefer to call getSubject()). 
      *
      * Returns false if no valid XMP metadata markers are present in the file.
      *
      * If an unexpected condition occurs, such as failure to open the 
      * file or a file which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * See also getSubject().
      *
      * @return boolean|array
      *
      */

    public function getSubjects()
    {
        return $this->getField('subject');
    }

    /**
      * Retrieve subject field or fields as a single string
      *
      * Returns a string consisting of all subject fields found
      * in the XMP metadata. If more than one is present, they
      * are joined by newlines. If there are no valid XMP
      * metadata markers in the file, this function returns false.
      *
      * If an unexpected condition occurs, such as failure to open the 
      * file or a file which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * See also getSubjects().
      *
      * @return boolean|string
      * 
      */

    public function getSubject()
    {
        return $this->getImplodedField('subject');
    }

    /**
      * Retrieve creator fields
      *
      * Returns an array consisting of all creator fields 
      * found in the XMP metadata (most images only have one,
      * so you may prefer to call getCreator()). 
      *
      * Returns false if no valid XMP metadata markers are present in the file.
      *
      * If an unexpected condition occurs, such as failure to open the 
      * file or a file which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * See also getCreator().
      *
      * @return boolean|array
      *
      */

    public function getCreators()
    {
        return $this->getField('creator');
    }

    /**
      * Retrieve creator field or fields as a single string
      *
      * Returns a string consisting of all creator fields found
      * in the XMP metadata. If more than one is present, they
      * are joined by newlines. If there are no valid XMP
      * metadata markers in the file, this function returns false.
      *
      * If an unexpected condition occurs, such as failure to open the 
      * file or a file which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * See also getCreators().
      *
      * @return boolean|string
      * 
      */

    public function getCreator()
    {
        return $this->getImplodedField('creator');
    }

    /**
      * Retrieve all instances of a specified field
      *
      * Returns an array consisting of all instances of a specified field
      * found in the XMP metadata.
      *
      * Returns false if no valid XMP metadata markers are present in the file.
      *
      * Also returns false if the specified field is not present.
      *
      * If an unexpected condition occurs, such as failure to open the 
      * file or a file which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * See also getImplodedField().
      *
      * @param string $field field name to fetch
      *
      * @return boolean|array
      *
      */

    public function getField($field)
    {
        if ($this->_xml === false) {
            if ($this->readXmp() === false) {
                return false;
            }
        }
        return $this->_xml->xpath("//dc:$field//rdf:li");
    }

    /**
      * Retrieve all instances of a specified field as a single string
      *
      * Returns a string consisting of all occurrences of the specified field
      * found in the XMP metadata. If more than one is present, they
      * are joined by newlines. If there are no valid XMP
      * metadata markers in the file, this function returns false.
      * This function also returns false if the specified field does not 
      * occur in the file.
      *
      * If an unexpected condition occurs, such as failure to open the 
      * file or a file which is not a valid JPEG datastream, a 
      * Image_JpegMarkerReaderOpenException or 
      * Image_JpegMarkerReaderDamagedException will be thrown.
      *
      * See also getCreators().
      *
      * @param string $field field name to fetch
      *
      * @return boolean|string
      * 
      */

    public function getImplodedField($field)
    {
        $result = $this->getField($field);
        if ($result) {
            return implode("\n", $result);
        }
        return false;
    }

    /**
      * A SimpleXML object containing the XMP data read.
      * To obtain this object, call readXmp directly before calling
      * any of the other information-gathering methods.
      */

    private $_xml = false;
}

