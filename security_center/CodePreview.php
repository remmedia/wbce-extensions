<?php
final class WbceSecurityCenterCodePreview
{
    public static function finding($database,$id)
    {
        $id=(int)$id;$result=$database->query("SELECT id,file_path,line_number,rule_id,message FROM `{TP}mod_security_center_findings` WHERE id=$id LIMIT 1");$finding=$result?$result->fetchRow(MYSQLI_ASSOC):null;if(!$finding)throw new RuntimeException('Der Fund wurde nicht gefunden.');
        $relative=str_replace('\\','/',ltrim((string)$finding['file_path'],'/'));if($relative===''||str_contains($relative,'::'))throw new RuntimeException('Diese Datei kann nicht direkt angezeigt werden.');
        $base=realpath(WB_PATH);$path=realpath(WB_PATH.'/'.$relative);if(!$base||!$path||($path!==$base&&!str_starts_with($path,$base.DIRECTORY_SEPARATOR))||!is_file($path))throw new RuntimeException('Die Funddatei liegt nicht im zulässigen WBCE-Verzeichnis.');
        $size=@filesize($path);if($size===false||$size>4194304)throw new RuntimeException('Die Datei ist für die sichere Vorschau zu groß.');$content=@file_get_contents($path);if(!is_string($content))throw new RuntimeException('Die Funddatei konnte nicht gelesen werden.');
        if(strpos($content,"\0")!==false)throw new RuntimeException('Binärdateien werden nicht als Quelltext angezeigt.');
        return array('id'=>$id,'file_path'=>$relative,'line'=>max(1,(int)$finding['line_number']),'rule_id'=>(string)$finding['rule_id'],'message'=>(string)$finding['message'],'content'=>$content);
    }

    public static function render($content,$focusLine)
    {
        $lines=array(1=>'');$line=1;$tokens=token_get_all((string)$content);
        foreach($tokens as $token){$text=is_array($token)?$token[1]:$token;$class=is_array($token)?self::tokenClass((int)$token[0]):'operator';$parts=explode("\n",str_replace("\r\n","\n",str_replace("\r","\n",$text)));foreach($parts as $index=>$part){if($index>0){$line++;$lines[$line]='';}if($part!=='')$lines[$line].='<span class="'.$class.'">'.htmlspecialchars($part,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</span>';}}
        $html='';foreach($lines as $number=>$value)$html.='<span class="sec-code-line'.($number===(int)$focusLine?' is-focus':'').'" data-line="'.$number.'"'.($number===(int)$focusLine?' data-code-focus':'').'><i>'.$number.'</i><code>'.($value!==''?$value:'&nbsp;').'</code></span>';
        return $html;
    }

    private static function tokenClass($id)
    {
        if(in_array($id,array(T_COMMENT,T_DOC_COMMENT),true))return 'comment';
        if(in_array($id,array(T_CONSTANT_ENCAPSED_STRING,T_ENCAPSED_AND_WHITESPACE),true))return 'string';
        if($id===T_VARIABLE)return 'variable';if(in_array($id,array(T_LNUMBER,T_DNUMBER),true))return 'number';
        if(in_array($id,array(T_OPEN_TAG,T_OPEN_TAG_WITH_ECHO,T_CLOSE_TAG),true))return 'tag';
        if(in_array($id,array(T_IF,T_ELSE,T_ELSEIF,T_FOR,T_FOREACH,T_WHILE,T_DO,T_SWITCH,T_CASE,T_DEFAULT,T_BREAK,T_CONTINUE,T_RETURN,T_FUNCTION,T_CLASS,T_INTERFACE,T_TRAIT,T_EXTENDS,T_IMPLEMENTS,T_NEW,T_CLONE,T_TRY,T_CATCH,T_FINALLY,T_THROW,T_NAMESPACE,T_USE,T_AS,T_PUBLIC,T_PROTECTED,T_PRIVATE,T_STATIC,T_ABSTRACT,T_FINAL,T_CONST,T_ECHO,T_PRINT,T_INCLUDE,T_INCLUDE_ONCE,T_REQUIRE,T_REQUIRE_ONCE,T_YIELD),true))return 'keyword';
        if($id===T_STRING)return 'identifier';return 'plain';
    }
}
