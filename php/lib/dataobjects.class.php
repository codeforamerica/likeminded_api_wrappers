<?php
/**
 * Collection of Data Classes.
 *
 * This source file contains object representations of the concepts in the likeminded API.
 *
 * You probably shouldn't be creating the objects below directly ever.  Use the
 * methods in the Api class to generate instances of these classes instead.  See
 * likeminded.php.
 *
 * The class is documented in the file itself. If you find any bugs please report them on 
 * GitHub (https://github.com/codeforamerica/likeminded_api_wrappers).
 *
 * License
 * Copyright (c) 2011, Code for America. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are 
 * permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of 
 *    conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list
 *    of conditions and the following disclaimer in the documentation and/or other materials
 *    provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from 
 *    this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties,
 * including, but not limited to, the implied warranties of merchantability and fitness 
 * for a particular purpose are disclaimed. In no event shall the author be liable for any
 * direct, indirect, incidental, special, exemplary, or consequential damages (including, 
 * but not limited to, procurement of substitute goods or services; loss of use, data, or 
 * profits; or business interruption) however caused and on any theory of liability, whether
 * in contract, strict liability, or tort (including negligence or otherwise) arising in 
 * any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author		John Mertens <john@codeforamerica.org>
 * @version		0.1
 *
 * @copyright	Copyright (c) 2011, Code for America. All rights reserved.
 * @license		BSD License
 */

/**
 * SearchResults 
 */
class SearchResults {
	public $available;	/**< The total number of references available in the results */
	public $next_page;	/**< The initial list of references available on the first page */
	public $references;	/**< A function that will return the results for the next page */
	
	public function __construct($available, $references, $next_page) {
		$this->available = $available;
		$this->references = $references;
		$this->next_page = $next_page;
	}
}

/**
 * Base class to hold references to project & resource data objects.  Used by the search method.
 * This class can only really be instantiated by one of the child classes below.
 */
class Reference {
	public $type;		/**< The type of the reference (either 'project' or 'resource'). */
	public $name;		/**< The name of the reference. */
	public $id;			/**< The id of the reference in the Likeminded database. */
	public $url;		/**< The url of the reference (without the protocal) */
	public $location; 	/**< The location of the reference. */
	
	/**
	 * Reference contructor. It returns false if the type is not set. Therefore, this class
	 * should be instantiated via ProjectReference or Resource Reference.
	 * 
	 */
	public function __construct($name='', $id, $url='', $location='') {
		if(in_array($this->type, array('project','resource'))) {
			$this->name = $name;
			$this->id = intval($id);
			$this->url = $url;
			$this->location = $location;
			return true;
		}
		return false;
	}
	
}

class ProjectReference extends Reference {
	public function __construct($name='', $id, $url='', $location='') {
		$this->type = 'project';
		parent::__construct($name, $id, $url, $location);
	}
}

class ResourceReference extends Reference {
	public function __construct($name='', $id, $url='', $location='') {
		$this->type = 'resource';
		parent::__construct($name, $id, $url, $location);
	}
}
?>