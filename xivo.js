// do like a jquery toggle but based on a parameter
$.fn.toggleFromValue = function(val) {
   if (val === 1
       || val === "1"
       || val === true) {
      this.show();
   } else {
      this.hide();
   }
}
