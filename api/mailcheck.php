<?php
	
    require 'mailer.php';
    require 'dbconnect.php';
    $attachmentDir = "/home/foiamachine/documents/";

    /* adapted from http://www.daniweb.com/web-development/php/threads/113444/php-imap-save-attachment-and-email */
    function getDecodeValue($message,$coding) {
       switch($coding) {
          case 0:
	  case 1:
	       $message = imap_8bit($message);
	       break;
	  case 2:
	       $message = imap_binary($message);
	       break;
          case 3:
	  case 5:
	       $message = imap_base64($message);
	       break;
	  case 4:
	       $message = imap_qprint($message);
	       break;
       }

       return $message;
    }

    /* extracts attachments and writes to files with the name of the attachment: part of the code 
       adapted from 
       http://www.daniweb.com/web-development/php/threads/113444/php-imap-save-attachment-and-email 
       */ 
    function extractAttachments($mbox, $message, $email_number){	 
    	 $attachments = array();
	 $structure = imap_fetchstructure($mbox, $email_number, FT_UID);
	 $parts = $structure->parts;
	 $fpos = 2;
	 for($i = 0; $i < count($parts); $i++){
	     $message['pid'][$i] = ($i);
	     $part = $parts[$i];
	     if($part->disposition == "ATTACHMENT"){
		 $message["type"][$i] = $message["attachment"]["type"][$part->type] . "/" . strtolower($part->subtype);
		 $message["subtype"][$i] = strtolower($part->subtype);
		 $ext=$part->subtype; 
		 $params = $part->dparameters; 
		 $filename = $part->dparameters[0]->value;
		 $mege = ""; 
		 $data = ""; 
		 $mege = imap_fetchbody($mbox,$jk,$fpos); 
		 $filename = "$filename";
		 $fp = fopen($savedir.$filename,w);
		 $data=getDeecodeValue($mege,$part->type); 
		 fputs($fp,$data); 
		 fclose($fp);
		 $fpos+=1;
	     }
	 }
    }

    function existAttachment($part){
       if(isset($part->parts)){
          foreach($part->parts as $partOfPart){
	      existAttachment($partOfPart);
	  }
       }else{
	  if(isset($part->disposition)){
	     if($part->disposition == 'attachment'){
	     	return true;
	     }
	  }
       }
    }

    function receiveMail(){
       $host = '{imap.gmail.com:993/imap/ssl}inbox';
       $login = 'requestengine@foiamachine.org';
       $password = 'foiamachine';

       $mbox = imap_open( $host, $login, $password ) or die ('Cannot connect to Gmail: ' . imap_last_error());
       $emails = imap_search($mbox, 'ALL');

       $attachmentDir = str_replace('\\', '/', $attachmentDir);
       if(substr($attachmentDir, strlen($attachmentDir) -1) != '/'){
          $attachmentDir .= '/';
       }

       if($emails){
          $message = array();
       	  $message['attachment']['type'][0] = 'text';
       	  $message['attachment']['type'][1] = 'multipart';
       	  $message['attachment']['type'][2] = 'message';
       	  $message['attachment']['type'][3] = 'application';
       	  $message['attachment']['type'][4] = 'image';
       	  $message['attachment']['type'][5] = 'audio';
       	  $message['attachment']['type'][6] = 'video';
       	  $message['attachment']['type'][7] = 'other';

	  rsort($emails);
	  foreach($emails as $email_number){
	     $overview = imap_fetch_overview($mbox, $email_number, 0);
	     /* extract request id from the subject */

	     $seen = $overview[0]->seen;
	     if(!$seen){
	     	  $body = imap_fetchbody($mbox, $email_number, 2);
	     	  $body = base64_decode($body);
	     	  $sub = $overview[0]->subject;
	     	  $index1 = strpos($sub, 'foiaid:') + strlen('foiaid:');
	     	  $index2 = strpos($sub, '-', index1);
	     	  $request_log_id = intval(substr($sub, $index1, $index2 - $index1));
	     	  $agency_id = intval(substr($sub, $index2+1, strlen($sub) - $index2 - 2));
	     	  $from = $overview[0]->from;
	     	  $sql = 'select U.email as user_email from users U, request_log R, request_log_agencies RA  
		       where R.id = :request_log_id and R.id = RA.request_log_id and 
		       RA.agency_id = :agency_id and R.user_id = U.id';
	     	  $db = getConnection();
	     	  $stmt = $db->prepare($sql);
		  	  $stmt->bindParam('request_log_id', $request_log_id);
		  	  $stmt->bindParam('agency_id', $agency_id);
		      $stmt->execute();
    		  $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    		  $result = $results[0];
	     	  sendMail($from, $result->user_email, $sub, $body);
	     	  		
	     	  $sql2 = 'insert into request_emails
	     	  		(request_log_id, agency_id, subject, body, outgoing) 
		     	  	 values( :request_log_id, :agency_id, :subject, :body, 0)';
		  $stmt = $db->prepare($sql2);
		  $stmt->bindParam('request_log_id', $request_log_id);
		  $stmt->bindParam('agency_id', $agency_id);
		  $stmt->bindParam('subject', $sub);
		  $stmt->bindParam('body', $body);
		  $stmt->execute();
	     }
	  }
       }
       imap_close($mbox);
    }
    receiveMail();
?>
