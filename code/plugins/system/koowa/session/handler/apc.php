<?php
/**
 * @version     $Id$
 * @category	Koowa
 * @package     Koowa_Session
 * @subpackage  Handler
 * @copyright   Copyright (C) 2007 - 2009 Johan Janssens and Mathias Verraes. All rights reserved.
 * @license     GNU GPL <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.koowa.org
 */

/**
 * APC Session Handler
 *
 * @author		Johan Janssens <johan@koowa.org>
 * @category	Koowa
 * @package		Koowa_Session
 * @subpackage  Handler
 */
class KSessionHandlerApc extends KSessionHandlerAbstract implements KSessionHandlerInterface
{
	/**
	 * Open the session handler.
	 *
	 * @param 	string 	The path to the session object.
	 * @param	string 	The name of the session.
	 * @return boolean  True on success, false otherwise.
	 */
	public function open($save_path, $session_name)
	{
		return true;
	}

	/**
	 * Close the session handler
	 *
	 * @return boolean  True on success, false otherwise.
	 */
	public function close()
	{
		return true;
	}

 	/**
 	 * Read the data for a particular session identifier.
 	 *
 	 * @param 	string The session identifier.
 	 * @return string  The session data.
 	 */
	public function read($id)
	{
		$sess_id = 'sess_'.$id;
		return (string) apc_fetch($sess_id);
	}

	/**
	 * Write session data to the session handler
	 *
	 * @param 	string 	The session identifier.
	 * @param 	string 	The session data.
	 * @return boolean  True on success, false otherwise.
	 */
	public function write($id, $session_data)
	{
		$sess_id = 'sess_'.$id;
		return apc_store($sess_id, $session_data, ini_get("session.gc_maxlifetime"));
	}

	/**
	  * Destroy the data for a particular session identifier.
	  *
	  * @param 	string 	The session identifier.
	  * @return boolean  True on success, false otherwise.
	  */
	public function destroy($id)
	{
		$sess_id = 'sess_'.$id;
		return apc_delete($sess_id);
	}

	/**
	 * Garbage collect stale sessions
	 *
	 * @param 	integer 	The maximum age of a session.
	 * @return boolean  True on success, false otherwise.
	 */
	public function gc($maxlifetime)
	{
		return true;
	}

	/**
	 * Test to see if the session store is available.
	 *
	 * @return boolean  True on success, false otherwise.
	 */
	public static function test() 
	{
		return extension_loaded('apc');
	}
}