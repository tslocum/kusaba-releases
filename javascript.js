var style_cookie;
var style_cookie_txt;
var tcmod_set = false;

/**
*
*  UTF-8 data encode / decode
*  http://www.webtoolkit.info/
*
**/

var Utf8 = {

	// public method for url encoding
	encode : function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";

		for (var n = 0; n < string.length; n++) {

			var c = string.charCodeAt(n);

			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}

		}

		return utftext;
	},

	// public method for url decoding
	decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;

		while ( i < utftext.length ) {

			c = utftext.charCodeAt(i);

			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}

		}

		return string;
	}

}

function replaceAll( str, from, to ) {
	var idx = str.indexOf( from );
	while ( idx > -1 ) {
		str = str.replace( from, to );
		idx = str.indexOf( from );
	}
	return str;
}

function insert(text)
{
	var textarea=document.forms.postform.message;
	if(textarea)
	{
		if(textarea.createTextRange && textarea.caretPos) // IE
		{
			var caretPos=textarea.caretPos;
			caretPos.text=caretPos.text.charAt(caretPos.text.length-1)==" "?text+" ":text;
		}
		else if(textarea.setSelectionRange) // Firefox
		{
			var start=textarea.selectionStart;
			var end=textarea.selectionEnd;
			textarea.value=textarea.value.substr(0,start)+text+textarea.value.substr(end);
			textarea.setSelectionRange(start+text.length,start+text.length);
		}
		else
		{
			textarea.value+=text+" ";
		}
		textarea.focus();
	}
}

function quote(b,a) { 
	var v=eval("document."+a+".message");
	v.value+=">>"+b+"\n";
	v.focus();
}

function highlight(post)
{
	var cells=document.getElementsByTagName("td");
	for(var i=0;i<cells.length;i++) if(cells[i].className=="highlight") cells[i].className="reply";

	var reply=document.getElementById("reply"+post);
	if(reply)
	{
		reply.className="highlight";
		var match=/^([^#]*)/.exec(document.location.toString());
		document.location=match[1]+"#"+post;
	}
}

function get_password(name)
{
	var pass=getCookie(name);
	if(pass) return pass;

	var chars="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	var pass='';

	for(var i=0;i<8;i++)
	{
		var rnd=Math.floor(Math.random()*chars.length);
		pass+=chars.substring(rnd,rnd+1);
	}

	return(pass);
}

function togglePassword() {
	/* Now IE/Opera safe, and 10% less fat! */
	var bSaf = (navigator.userAgent.indexOf('Safari') != -1);
	var bOpera = (navigator.userAgent.indexOf('Opera') != -1);
	var bMoz = (navigator.appName == 'Netscape');
	var passwordbox = document.getElementById("passwordbox");
	var passwordbox_html;
	
	if ((bSaf) || (bOpera) || (bMoz))
		passwordbox_html = passwordbox.innerHTML;
	else passwordbox_html = passwordbox.text;
	
	passwordbox_html = passwordbox_html.toLowerCase();
	
	if (passwordbox_html=='<td></td><td></td>') {
		if ((bSaf) || (bOpera) || (bMoz))
			passwordbox.innerHTML = '<td class="postblock">Mod</td><td><input type="password" name="modpassword" size="28" maxlength="75" />&nbsp;<acronym title="Distplay staff status (Mod/Admin)">D</acronym>:&nbsp;<input type="checkbox" name="displaystaffstatus" checked>&nbsp;<acronym title="Lock">L</acronym>:&nbsp;<input type="checkbox" name="lockonpost" />&nbsp;&nbsp;<acronym title="Sticky">S</acronym>:&nbsp;<input type="checkbox" name="stickyonpost" />&nbsp;&nbsp;<acronym title="Raw HTML">RH</acronym>:&nbsp;<input type="checkbox" name="rawhtml" /></td>';
		else passwordbox.text = '<td class="postblock">Mod</td><td><input type="password" name="modpassword" size="28" maxlength="75" />&nbsp;<acronym title="Distplay staff status (Mod/Admin)">D</acronym>:&nbsp;<input type="checkbox" name="displaystaffstatus" checked>&nbsp;<acronym title="Lock">L</acronym>:&nbsp;<input type="checkbox" name="lockonpost" />&nbsp;&nbsp;<acronym title="Sticky">S</acronym>:&nbsp;<input type="checkbox" name="stickyonpost" />&nbsp;&nbsp;<acronym title="Raw HTML">RH</acronym>:&nbsp;<input type="checkbox" name="rawhtml" /></td>';
	} else {
		if ((bSaf) || (bOpera) || (bMoz))
			passwordbox.innerHTML = '<td></td><td></td>';
		else passwordbox.text = '<td></td><td></td>';
	}
	return false;
}

function getCookie(name)
{
	with(document.cookie)
	{
		var regexp=new RegExp("(^|;\\s+)"+name+"=(.*?)(;|$)");
		var hit=regexp.exec(document.cookie);
		if(hit&&hit.length>2) return Utf8.decode(unescape(replaceAll(hit[2],'+','%20')));
		else return '';
	}
}

function set_cookie(name,value,days)
{
	if(days)
	{
		var date=new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires="; expires="+date.toGMTString();
	}
	else expires="";
	document.cookie=name+"="+value+expires+"; path=/";
}

function set_stylesheet(styletitle,txt)
{
    if (txt) {
        set_cookie("tcstyle_txt",styletitle,365);
    } else {
        set_cookie("tcstyle",styletitle,365);
	}

	var links=document.getElementsByTagName("link");
	var found=false;
	for(var i=0;i<links.length;i++)
	{
		var rel=links[i].getAttribute("rel");
		var title=links[i].getAttribute("title");
		
		if(rel.indexOf("style")!=-1&&title)
		{
			links[i].disabled=true; // IE needs this to work. IE needs to die.
			if(styletitle==title) { links[i].disabled=false; found=true; }
		}
	}
	if(!found) set_preferred_stylesheet();
}

function set_preferred_stylesheet()
{
	var links=document.getElementsByTagName("link");
	for(var i=0;i<links.length;i++)
	{
		var rel=links[i].getAttribute("rel");
		var title=links[i].getAttribute("title");
		if(rel.indexOf("style")!=-1&&title) links[i].disabled=(rel.indexOf("alt")!=-1);
	}
}

function get_active_stylesheet()
{
	var links=document.getElementsByTagName("link");
	for(var i=0;i<links.length;i++)
	{
		var rel=links[i].getAttribute("rel");
		var title=links[i].getAttribute("title");
		if(rel.indexOf("style")!=-1&&title&&!links[i].disabled) return title;
	}
	return null;
}

function get_preferred_stylesheet()
{
	var links=document.getElementsByTagName("link");
	for(var i=0;i<links.length;i++)
	{
		var rel=links[i].getAttribute("rel");
		var title=links[i].getAttribute("title");
		if(rel.indexOf("style")!=-1&&rel.indexOf("alt")==-1&&title) return title;
	}
	return null;
}

function delandbanlinks(spanid,board,postid,isthread) {
    if (!tcmod_set) { return; }
    var dnbspan = document.getElementById(spanid);
    var newhtml = '';
    newhtml += '&nbsp;&#91;<a href="/manage.php?action=delposts&boarddir=' + board + '&del';
    if (isthread) {
        newhtml += 'thread';
    } else {
        newhtml += 'post';
    }
    newhtml += 'id=' + postid + '" title="Delete" onclick="return confirm(\'Are you sure you want to delete this post/thread?\');">D<\/a>&nbsp;<a href="/manage.php?action=delposts&boarddir=' + board + '&del';
    if (isthread) {
        newhtml +='thread';
    } else {
        newhtml += 'post';
    }
    newhtml +='id=' + postid + '&postid=' + postid + '" title="Delete &amp; Ban" onclick="return confirm(\'Are you sure you want to delete and ban the poster of this post/thread?\');">&amp;<\/a>&nbsp;<a href="/manage.php?action=bans&banboard=' + board + '&banpost=' + postid + '" title="Ban">B<\/a>&#93;';
    dnbspan.innerHTML = newhtml;
}

function set_inputs(id) { with(document.getElementById(id)) {if(!name.value) name.value=getCookie("name"); if(!em.value) em.value=getCookie("email"); if(!postpassword.value) postpassword.value=get_password("postpassword"); } }
function set_delpass(id) { with(document.getElementById(id)) postpassword.value=getCookie("postpassword"); }

window.onunload=function(e)
{
	if(style_cookie)
	{
		var title=get_active_stylesheet();
		set_cookie(style_cookie,title,365);
	}
	
	if(style_cookie_txt)
	{
		var title=get_active_stylesheet();
		set_cookie(style_cookie_txt,title,365);
	}
}

window.onload=function(e)
{
	var match;

	if(match=/#i([0-9]+)/.exec(document.location.toString()))
	if(!document.forms.postform.message.value)
	insert(">>"+match[1]);

	if(match=/#([0-9]+)/.exec(document.location.toString()))
	highlight(match[1]);

}

if(style_cookie) {
	var cookie=getCookie(style_cookie);
	var title=cookie?cookie:get_preferred_stylesheet();

	set_stylesheet(title);
}

if(style_cookie_txt) {
	var cookie=getCookie(style_cookie_txt);
	var title=cookie?cookie:get_preferred_stylesheet();

	set_stylesheet(title);
}

if (getCookie('tcmod')=='yes') {
    tcmod_set = true;
}