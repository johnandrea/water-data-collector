<script>
function invertAll() {
  var theForm = document.getElementById('cleaning-table');
  var theBoxes = theForm.getElementsByTagName('input');
  for ( var i=0; i < theBoxes.length; i++) {
     if ( theBoxes[i].type == "checkbox") {
        theBoxes[i].checked = ! theBoxes[i].checked;
     }
  }
}
function setAll( newState ) {
  var theForm = document.getElementById('cleaning-table');
  var theBoxes = theForm.getElementsByTagName('input');
  for ( var i=0; i < theBoxes.length; i++) {
     if ( theBoxes[i].type == "checkbox") {
        theBoxes[i].checked = newState;
     }
  }
}
</script>
