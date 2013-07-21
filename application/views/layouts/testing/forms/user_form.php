<form name="{form:name}" id="{form:name}" action="{form:action}" method="POST" >
<label for="{caption:u_name}">Email: </label><input name="{name:u_name}" type="{HTML_type:u_name}" id="{caption:u_name}" value="{value:u_name}" {r_only:u_name}/><br/>
<label for="{caption:u_soname}">Password: </label><input name="{name:u_soname}" type="{HTML_type:u_soname}" id="{caption:u_soname}" value="{value:u_soname}" {r_only:u_soname}/><br/>
<label for="{caption:u_settings}">Settings: </label><input name="{name:u_settings}" type="text" id="{caption:u_settings}" value="{value:u_settings}" {r_only:u_settings}/><br/>

<p>Gender:</p>
<label for="{id:u_gender}" >
  {caption:u_gender}
</label>
<input id="{id:u_gender}" type="radio" name="{name:u_gender}" value="{value:u_gender}" {checked:u_gender} />


<p>Timezone:</p>
<select>
  <option value="volvo">Volvo</option>
  <option value="saab">Saab</option>
  <option value="mercedes">Mercedes</option>
  <option value="audi">Audi</option>
</select>
<br/>
<input type="submit" value="Update"/>
</form>


