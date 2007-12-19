<?php
	// @title	Auth class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-12-22
	// @desc	Handles authentication. Simple, no? However, this needs to be
	//			altered to integrate with the current authentication system
	// @requires stdexception.php (StdException class)
	// @requires modles/user.php (User model)
	
	include_once('extexception.php');
	
	// classes
	class Meta {
		// record activity
		public static function record_activity($activity, $file_id = null, $accessed_at = null) {
			// record activity
			$meta_file = new meta_file();
			if($file_id != null) $meta_file->file_id = $file_id;
			$meta_file->user_id = self::session_user_id();
			$meta_file->activity_id = activity::find_activity_id($activity);
			$meta_file->accessed_at = ($accessed_at == null) ? date('Y-m-d H:i:s') : $accessed_at;
			$meta_file->save();
		}
		public static function login() {
			// record login activity
			self::record_activity('login');
		}
		
		public static function logout() {
			// record logout activity
			self::record_activity('logout');
		}
		
		public static function download($file_id) {
			// record logout activity
			self::record_activity('download', $file_id);
		}
		
		public static function upload($file_id) {
			// record logout activity
			self::record_activity('upload', $file_id);
		}
		
		public static function update($file_id) {
			// record logout activity
			self::record_activity('update', $file_id);
		}
		
		public static function approve($file_id) {
			// record file approval
			self::record_activity('approve', $file_id);
		}
		
		public static function disable($file_id) {
			// record file disabling
			self::record_activity('disable', $file_id);
		}
		
		public static function delete($file_id) {
			// record file deletion
			self::record_activity('delete', $file_id);
		}
		
		// get session data
		private static function session_user_id() {
			$session = Session::retreive();
			return $session->auth->id;
		}
	}
	
	class MetaException extends ExtException {} // shouldn't have to be used
?>