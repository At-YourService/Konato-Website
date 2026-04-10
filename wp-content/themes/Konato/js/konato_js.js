// function openNav() {
//     document.getElementById("myNav").style.display = "block";
// }

// function closeNav() {
//     document.getElementById("myNav").style.display = "none";
// }

// jQuery(document).ready(function () {
//     jQuery("span.hamburger").click(function () {
//         jQuery(".overlay").show(1000);
//     });
//     jQuery("a.closebtn").click(function () {
//         jQuery(".overlay").hide;
//     });

// });

jQuery(document).ready(function () {
    jQuery("a.closebtn").click(function () {
        jQuery(".overlay").removeClass("open");
    });
    jQuery("span.hamburger").click(function () {
        jQuery(".overlay").addClass("open");
    });
});

jQuery(document).ready(function () {
    jQuery("a").on('click', function (event) {
        if (this.hash !== "") {
            event.preventDefault();
            var hash = this.hash;
            jQuery('html, body').animate({
                scrollTop: jQuery(hash).offset().top
            }, 800, function () {
                window.location.hash = hash;
            });
        }
    });
});


jQuery(document).ready(function () {
    var itemsMainDiv = ('.MultiCarousel');
    var itemsDiv = ('.MultiCarousel-inner');
    var itemWidth = "";

    jQuery('.leftLst, .rightLst').click(function () {
        var condition = jQuery(this).hasClass("leftLst");
        if (condition)
            click(0, this);
        else
            click(1, this)
    });

    ResCarouselSize();




    jQuery(window).resize(function () {
        ResCarouselSize();
    });

    //this function define the size of the items
    function ResCarouselSize() {
        var incno = 0;
        var dataItems = ("data-items");
        var itemClass = ('.item');
        var id = 0;
        var btnParentSb = '';
        var itemsSplit = '';
        var sampwidth = jQuery(itemsMainDiv).width();
        var bodyWidth = jQuery('body').width();
        jQuery(itemsDiv).each(function () {
            id = id + 1;
            var itemNumbers = jQuery(this).find(itemClass).length;
            btnParentSb = jQuery(this).parent().attr(dataItems);
            itemsSplit = btnParentSb.split(',');
            jQuery(this).parent().attr("id", "MultiCarousel" + id);


            if (bodyWidth >= 1200) {
                incno = itemsSplit[3];
                itemWidth = sampwidth / incno;
            }
            else if (bodyWidth >= 992) {
                incno = itemsSplit[2];
                itemWidth = sampwidth / incno;
            }
            else if (bodyWidth >= 768) {
                incno = itemsSplit[1];
                itemWidth = sampwidth / incno;
            }
            else {
                incno = itemsSplit[0];
                itemWidth = sampwidth / incno;
            }
            jQuery(this).css({ 'transform': 'translateX(0px)', 'width': itemWidth * itemNumbers });
            jQuery(this).find(itemClass).each(function () {
                jQuery(this).outerWidth(itemWidth);
            });

            jQuery(".leftLst").addClass("over");
            jQuery(".rightLst").removeClass("over");

        });
    }


    //this function used to move the items
    function ResCarousel(e, el, s) {
        var leftBtn = ('.leftLst');
        var rightBtn = ('.rightLst');
        var translateXval = '';
        var divStyle = jQuery(el + ' ' + itemsDiv).css('transform');
        var values = divStyle.match(/-?[\d\.]+/g);
        var xds = Math.abs(values[4]);
        if (e == 0) {
            translateXval = parseInt(xds) - parseInt(itemWidth * s);
            jQuery(el + ' ' + rightBtn).removeClass("over");

            if (translateXval <= itemWidth / 2) {
                translateXval = 0;
                jQuery(el + ' ' + leftBtn).addClass("over");
            }
        }
        else if (e == 1) {
            var itemsCondition = jQuery(el).find(itemsDiv).width() - jQuery(el).width();
            translateXval = parseInt(xds) + parseInt(itemWidth * s);
            jQuery(el + ' ' + leftBtn).removeClass("over");

            if (translateXval >= itemsCondition - itemWidth / 2) {
                translateXval = itemsCondition;
                jQuery(el + ' ' + rightBtn).addClass("over");
            }
        }
        jQuery(el + ' ' + itemsDiv).css('transform', 'translateX(' + -translateXval + 'px)');
    }

    //It is used to get some elements from btn
    function click(ell, ee) {
        var Parent = "#" + jQuery(ee).parent().attr("id");
        var slide = jQuery(Parent).attr("data-slide");
        ResCarousel(ell, Parent, slide);
    }

});