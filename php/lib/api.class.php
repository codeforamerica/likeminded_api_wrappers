<?php
/**
 * Api class
 *
 * This source file can be used to communicate with Likeminded (http://likeminded.com)
 *
 * The class is documented in the file itself. If you find any bugs please report them on GitHub (https://github.com/codeforamerica/likeminded_api_wrappers).
 *
 * License
 * Copyright (c) 2011, Code for America. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author		John Mertens <john@codeforamerica.org>
 * @version		0.1
 *
 * @copyright	Copyright (c) 2011, Code for America. All rights reserved.
 * @license		BSD License
 */
require 'connection.class.php';
require 'dataobjects.class.php';
require 'utils/xml2json/xml2json.php';
/**
 * Api is the main class of the Likeminded API wrapper.
 */
class Api {

	private $_key;			/**< Private variable for the Likeminded API key */
	private $_connection;	/**< Private variable to store the connection object */
	
	/**
	 * The constructor for Api
	 * This constructor stores the API key and sets up a connection if there isn't one already
	 * @param $key The Likeminded API key to use for requests.
	 * @param $connection (optional) A connection object.
	 * @return An Api object
	 */
	public function __construct($key, $connection = false) {
		$this->_key = $key;
		$this->_connection = ($connection) ? $connection : new Connection('http://v1.api.likeminded.exygy.com');
	}
	
	/**
	 * Search Likeminded resources or projects.
	 * 
	 * Return a ``SearchResults`` object.  This object behaves kind of like a
	 * list, except that it doesn't support indexing.  You can check the number
	 * of objects it contains with ``len(results)``, and iterate through it 
	 * with ``foreach($results as $reference) ...``.
	 * 
	 * The objects contained in the ``SearchResults`` object are references
	 * to projects and resources.  They have four attributes: ``name``, 
	 * ``type``, ``url``, and ``id``.
	 * 
	 * @param $query The term(s) to search for
	 * @param $category (optional) Comma separated list of category ids to limit the search. Defaults to all categories.
	 * @param $subcategory (optional) Comma separated list of subcategory ids to limit the search. Defaults to all.
	 * @param $type (optional) Type of entities to search ('Project' or 'Resource'). Defaults to 'All'.
	 * @param $status (optional) Only used when type=Project. 0 = Starting up, 1 = Ongoing, 2 = Completed. Defaults to all.
	 * @param $sort (optional) Sort results by 'Relevance' or 'Recent'. Defaults to both.
	 * @return A SearchResults object. The results have four fields: 'name', 'type', 'url' and 'id'.
	 */
	public function search($query='', $category=false, $subcategory=false, $type='All', $status='All', $sort='All') {
        return $this->_search_helper($query, $category, $subcategory, $type, $status, $sort, 1);
	}
	
	/**
	 * A helper search function that is aware of pagination.  Pagination is
     * abstracted out of the public search function so that the user has no
     * need to be aware of it.
	 * 
	 * @param
	 */
	private function _search_helper($query, $category, $subcategory, $type, $status, $sort, $page) {
        
        # Process the arguments.
		$category = (is_array($category)) ? implode(',', $category) : strval($category);
        
		$subcategory = (is_array($subcategory)) ? implode(',', $subcategory) : strval($subcategory);
        
        $search_terms = array('query' => $query,
                         'category' => $category, 
                         'subcategory' => $subcategory, 
                         'type' => $type, 
                         'status' => $status, 
                         'sort' => $sort,
                         'page' => $page, 
                         'apikey' => $this->_key);

		$search_xml = $this->_connection->get('/search/', $search_terms);
		$xml = simplexml_load_string($search_xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		
		$results = $this->_make_search_results($xml, 
					$this->_search_helper($query, $category, $subcategory, $type, $status, $sort, $page+1));
		
		return $results;
		
        /*
       	 search_dict = xml2dict(search_xml)
	        results_dict = search_dict.search_results.results

	        results = self.__make_search_results(
	            results_dict, 
	            lambda: self.__search_helper(query, category, subcategory, 
	                                         type, status, sort, page+1))
	        return results
		*/
	
	}
	
	/**
	 * The SearchResults factory function
	 */
	private function _make_search_results($results_dict, $next_page, $ResultsClass='SearchResults') {
		$projArr = array();
		for($x=0; $x < count($results_dict->results->project); $x++) {
			array_push($projArr, $results_dict->results->project[$x]);
		}
		
		$resrcArr = array();
		for($x=0; $x < count($results_dict->results->resource); $x++) {
			array_push($resrcArr, $results_dict->results->resource[$x]);
		}		

		$projects = $this->_make_references($projArr, 'ProjectReference');
        $resources = $this->_make_references($resrcArr, 'ResourceReference');

		$available = intval($results_dict->available);
		$references = array_merge($projects, $resources);
		$results = new SearchResults($available,$references, $next_page);

        return $results;
	}
	
	/**
	 * A factory method that creates an array of Reference objects.
	 */
	private function _make_references($ref_list, $RefClass) {
		if(!is_array($ref_list)) $ref_list = array($ref_list);
		
		$references = array();
		foreach($ref_list as $ref_obj) {
			$reference = new $RefClass((string) $ref_obj->name, (int) $ref_obj->id, (string) $ref_obj->likeminded_url, (string) $ref_obj->location);
			array_push($references, $reference);
		}
		
		return $references;
	}
}
?>