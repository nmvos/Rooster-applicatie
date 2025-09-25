// give a div or td the class colored and a data-color='backgroundcolor' to change the color text dynamicly on page load

function hexToRgb(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
   return result ? {
       r: parseInt(result[1], 16),
       g: parseInt(result[2], 16),
       b: parseInt(result[3], 16)
   } : null;
}

function toLinear(color) {
   let linearColor = color/255
   if (linearColor <= 0.04045) {
       linearColor/12.92
   } else{
       linearColor = Math.pow((linearColor + 0.055) / 1.055, 2.4);
   }
   return linearColor;
}

//Call this function to change text colors after page load
function checkColors(item){    
    let bghex = item.getAttribute('data-color');
    if (bghex !== null){
        let r = hexToRgb(bghex).r;
        let g = hexToRgb(bghex).g;
        let b = hexToRgb(bghex).b;
        let rLinear = toLinear(r);
        let gLinear = toLinear(g);
        let bLinear = toLinear(b);
        let luminance = 0.2126*rLinear + 0.7152*gLinear + 0.0722*bLinear
        const textColour = (luminance > 0.5) ? 'black' : 'white';
        item.style.color = textColour  
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if(window.location.pathname != "/admin/concept_rooster"){
        document.querySelectorAll('.colored').forEach(function(item) {
            checkColors(item);
        });
    }

});