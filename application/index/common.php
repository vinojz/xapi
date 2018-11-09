<?php
    //获取评论
	function getComment($blogId)
	{
    	$list = db('comment')->where('blog_id', $blogId)->select();
    	if(empty($list)){
        	echo "";
    	}else{
        	$html = '';
        	foreach($list as $key=>$vo){
            	$html .= '<a href="javascript:;" class="pull-left"><img alt="image" src="' . $vo['com_avatar'] . '"></a>';
            	$html .= '<div class="media-body"><a href="javascript:;" style="color:#337AB7">' . $vo['com_user'];
            	$html .= '&nbsp;&nbsp;&nbsp;&nbsp;</a>' . $vo['content'] . '<br/>';
            	$html .= '<small class="text-muted">' . date('Y-m-d H:i', $vo['com_time']) . '</small></div>';
        }
        echo $html;
    }
}