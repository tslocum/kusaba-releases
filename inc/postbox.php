<?php
require("../config.php");
?>
<div class="postarea">
<form id="postform" action="<?php echo $chan_webpath.'/'.$_GET['board']; ?>/board.php" method="post" enctype="multipart/form-data">
<p>
<table><tbody>
<tr>
<td class="postblock">Name</td><td><input type="text" name="name" size="28" maxlength="75" /></td>
</tr>
<tr>
<td class="postblock">E-mail</td><td><input type="text" name="email" size="28" maxlength="75" /></td>
</tr>
<tr>
<td class="postblock">Subject</td><td><input type="text" name="subject" size="35" maxlength="75" /><input type="submit" value="Submit" /></td>
</tr>
<tr>
<td class="postblock">Messag<a href="#" onclick="togglePassword();" style="color: inherit; text-decoration: none;">e</a></td><td><textarea name="message" cols="48" rows="4"></textarea></td>
</tr>
<tr><td class="postblock">File</td><td><input type="file" name="imagefile" size="35" /></td>
</tr>
<tr id="passwordbox"></tr>
<tr>
<td colspan="2"> <div class="rules"><ul> <li>Supported file types are: GIF, JPG, PNG</li> <li>Maximum file size allowed is 1000 KB.</li> <li>Images greater than 200x200 pixels will be thumbnailed.</li> </ul> </div></td>
</tr>
</tbody></table>
</p>
</form>
</div>
<hr />