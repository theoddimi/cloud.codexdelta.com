import "../css/main.css";

import _ from 'lodash';

window._ = _;

import $ from 'jquery';

window.$ = $;
const test = () => {
    console.log('test');
};

test();

$(document).ready(function () {
    $('.trigger-loading').on('click', function () {
        $('body').css("background-color", "#00000047");
        $('body').append('<div style="position:fixed; width:100%; height: 100%; top: 0;"><div id="loader" style="position: absolute; top: 45%; left: 50%; width: 50px; height: 50px; z-index: 10; background-color: #336699"></div></div>');
        startLoaderEffect();
    });
})
;

function startLoaderEffect()
{
    let borderRadius = 35;
    let left = Math.floor(Math.random() * 101) - 50;
    let top = Math.floor(Math.random() * 101) - 50;
    setInterval(function() {
        left = Math.floor(Math.random() * 101) - 50;
        top = Math.floor(Math.random() * 101) - 50;

        $('#loader').animate({
            left: "+=" + left,
            top: "+=" + top,
            borderRadius: borderRadius+"px"
        }, 1000);


        if (Math.sign(borderRadius) === 1) {
            borderRadius = 0;
        } else {
            borderRadius = 35
        }
    }, 100)
}

