<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PasswordReminder extends MY_Controller {

    public function __construct () {
        parent::__construct();
    }

    public function index () {
	    $this->display('/passwordReminder/index.twig');
    }

    public function sendMail () {

    	$mid = $this->input->post('mid');
    	$mail = $this->input->post('mail');
    	if (!$mid || !$mail) {
    		header('Location: /passwordReminder');
    		return true;
	    }

	    $sql = " select * from user where mid = ? and email = ? ";
    	$user = $this->db->query($sql, [$mid, $mail])->row();
    	if (!$user) {
		    header('Location: /passwordReminder');
		    return true;
	    }

	    $sql = " delete from password_reminder where user_id = ? ";
    	$this->db->query($sql, [$user->id]);

	    $authKey = md5(microtime());
	    $values = [
	    	'user_id' => $user->id,
		    'auth_key' => $authKey,
	    ];
    	$this->db->insert('password_reminder', $values);

    	$body = "以下のリンクからパスワードを再設定してください。\n"
	        . "https://" . Meow::FQDN . "/passwordReminder/newPassword/{$authKey}";

	    $this->load->library('email');
	    $this->email->to($mail);
	    $this->email->from('noreply@' . Meow::FQDN);
    	$this->email->subject(Meow::FQDN . " パスワード再設定");
    	$this->email->message($body);
    	$this->email->send();

    	header('Location: /passwordReminder/sendMailSuccess');
    }

    public function sendMailSuccess () {
    	$this->display('/passwordReminder/sendMailSuccess.twig');
    }

    public function newPassword (string $authKey) {
    	if (!$authKey) {
		    header('Location: /passwordReminder');
		    return true;
	    }

	    // 1日前以前のデータは無効
	    $oneDayAgo = date('Y-m-d H:i:s', strtotime(' - 1 day '));
    	$sql = " delete from password_reminder where create_at < ? ";
    	$this->db->query($sql, [$oneDayAgo]);

	    $sql = "
	        select u.*
	        from
	        	password_reminder r 
	        	inner join user u 
	        		on u.id = r.user_id
            where
            	r.auth_key = ?
	    ";
    	$user = $this->db->query($sql, [$authKey])->row();
    	if (!$user) {
		    header('Location: /passwordReminder');
		    return true;
	    }

	    $this->display('/passwordReminder/newPassword.twig', [
	    	'user' => $user,
		    'authKey' => $authKey,
	    ]);
    }

    public function updatePassword () {
    	$authKey = $this->input->post('authKey');
	    $password = $this->input->post('password');
	    $passwordConfirm = $this->input->post('passwordConfirm');
	    if (!$authKey || !$password || !$passwordConfirm || $password != $passwordConfirm) {
		    header('Location: /passwordReminder');
		    return true;
	    }

	    $sql = "
	        select u.*
	        from
	        	password_reminder r 
	        	inner join user u 
	        		on u.id = r.user_id
            where
            	r.auth_key = ?
	    ";
	    $user = $this->db->query($sql, [$authKey])->row();
	    if (!$user) {
		    header('Location: /passwordReminder');
		    return true;
	    }

	    $loginAuthKey = md5(microtime() . Meow::SALT);
	    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
	    $values = [
		    'password_hash' => $passwordHash,
		    'auth_key' => $loginAuthKey,
	    ];
	    $this->db->update('user', $values, " id = {$user->id} ");

	    $sql = " delete from password_reminder where user_id = ? ";
	    $this->db->query($sql, [$user->id]);

	    // ログイン
	    setcookie('mid', $user->mid, time()+60*60*24*365, '/');
	    setcookie('auth', $loginAuthKey, time()+60*60*24*365, '/');

	    header('Location: /');
    }

}
