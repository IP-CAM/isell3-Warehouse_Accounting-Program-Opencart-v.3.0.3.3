<?php

class Utils extends CI_Model {

    public function spellAmount($number, $unit = NULL, $return_cents = true) {
	if (!$unit) {
	    $unit[0] = array('копійка', 'копійки', 'копійок');
	    $unit[1] = array('гривня', 'гривні', 'гривень');
	    $unit[2] = array('тисяча', 'тисячи', 'тисяч');
	    $unit[3] = array('мільон', 'мільони', 'мільонів');
	}
	$millions = $this->getNumberPosition($number, 1000000, 100);
	$thousands = $this->getNumberPosition($number, 1000, 100);
	$ones = $this->getNumberPosition($number, 1, 100);
	$cents = $this->getNumberPosition($number * 100, 1, 10);

	$str = $this->spellNumber($millions, $unit[3]) . ' ' . $this->spellNumber($thousands, $unit[2]) . ' ' . $this->spellNumber($ones, $unit[1]) . ' ' . $this->spellNumber($cents, $unit[0], $return_cents);
	$str = trim($str);
	return mb_strtoupper(mb_substr($str, 0, 1, 'utf-8'), 'utf-8') . mb_substr($str, 1, mb_strlen($str) - 1, 'utf-8');
    }

    public function spellNumber($number, $units=null, $ret_number = false) {
	$hundreds_i = $this->getNumberPosition($number, 100, 1);
	$tens_i = $this->getNumberPosition($number, 10, 1);
	$ones_i = $this->getNumberPosition($number, 1, 1);
	if (!($hundreds_i || $tens_i || $ones_i) && !$ret_number){
	    return '';
	}
	if( $units ){
	    if ($ones_i === 1 && $tens_i != 1){
		$unit = $units[0];
	    } else 
	    if ($ones_i > 1 && $ones_i < 5){
		$unit = $units[1];
	    } else {
		$unit = $units[2];
	    }
	    if ($ret_number) {
		if ($number < 10)
		    return "0$number $unit";
		return "$number $unit";
	    }
	} else {
	    $unit='';
	}

	$ones = array("", "одна", "дві", "три", "чотири", "п'ять", "шість", "сім", "вісім", "дев'ять");
	$tens = array("", "десять", "двадцять", "тридцять", "сорок", "п'ятдесят", "шістдесят", "сімдесят", "вісімдесят", "дев'яносто");
	$teens = array("", "одинадцять", "дванадцять", "тринадцять", "чотирнадцять", "п'ятнадцять", "шістнадцять", "сімнадцять", "вісімнадцять", "дев'ятнадцять");
	$hundreds = array("", "сто", "двісті", "триста", "чотириста", "п'ятсот", "шістсот", "сімсот", "вісімсот", "дев'ятсот");

	if ($tens_i == 1){
	    return "$hundreds[$hundreds_i] $teens[$ones_i] $unit";
	}
	else{
	    return "$hundreds[$hundreds_i] $tens[$tens_i] $ones[$ones_i] $unit";
	}
    }

    private function getNumberPosition($number, $position, $range = 1) {//DEPRECATED
	$number-=$position * 10 * $range * floor($number / $position / 10 / $range);
	return floor($number / $position);
    }
    public function getLocalDate($tstamp) {
	$time = strtotime($tstamp);
	$months = array("січня", "лютого", "березня", "квітня", "травня", "червня", "липня", "серпня", "вересня", "жовтня", "листопада", "грудня");
	return date('d', $time) . ' ' . $months[date('m', $time) - 1] . ' ' . date('Y', $time);
    }

    private function sendEmail($to,$subject,$body,$file=null){
        $this->Base->set_level(1);
        $this->load->library('email');
        $this->email->initialize([
            'useragent'=>'iSell',
            'protocol'=>'smtp',
            'charset'=>'utf8',
            'smtp_host'=>BAY_SMTP_SERVER,
            'smtp_user'=>BAY_SMTP_USER,
            'smtp_pass'=>BAY_SMTP_PASS
        ]);
        $this->email->from(BAY_SMTP_SENDER_MAIL,BAY_SMTP_SENDER_NAME);
        $this->email->to($to);
        $this->email->cc(BAY_SMTP_SENDER_MAIL);
        $this->email->subject($subject);
        $this->email->message($body);
        if( $file ){
	    $this->email->attach($file['data'], 'attachment', $file['name'], $file['mime']);
	}
        return $this->email->send();
    }
    public function postEmail(){
	$to=$this->input->get_post('to');
	$subject=$this->input->get_post('subject');
	$body=$this->input->get_post('body');
	$doc_view_id=$this->input->get_post('doc_view_id');
	$fgenerator=$this->input->get_post('fgenerator');
        $out_type=$this->input->get_post('out_type');
        $send_file=$this->input->get_post('send_file');
	$file=$send_file?$this->generateFile($fgenerator,$doc_view_id,$out_type,$subject):null;
	return $this->sendEmail($to, $subject, $body, $file);
    }
    private $mimes=[
        '.html'=>'text/html',
        '.xls'=>'application/vnd.ms-excel',
        '.xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        '.pdf'=>'application/pdf'
        ];
    private function generateFile($fgenerator,$doc_view_id,$out_type='.xlsx',$subject='file'){
        $file=[];
        $Generator=$this->Base->load_model($fgenerator);
        $file['data']=$Generator->viewFileGet($doc_view_id,$out_type);
        $file['mime']=$this->mimes[$out_type];
        $file['name']=  str_replace(' ', '_', $subject).$out_type;
        return $file;
    }
}
