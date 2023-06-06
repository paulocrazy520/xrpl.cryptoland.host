(function ($) {
    'use strict';

    let tabTypeIdArray = { "*": "total", ".unclaimed": "unclaimed", ".unrevealed": "unrevealed", ".revealed": "revealed" };

    $(function () {
        updateShowingResult();
    });

    function getTotalCount(key = "*") {
        return Math.ceil($("#" + tabTypeIdArray[key] + "Count").val());
    }

    function updateShowingResult(key = "*") {
        var totalCount = $("#" + tabTypeIdArray[key] + "Count").val();

        if (!totalCount || totalCount == 0) {
            $("#empty_result").show();
        }
        else {
            $("#empty_result").hide();
        }


        var cardsCount = $('#nft-list').find(key == "*" ? ".nft-card" : key).toArray().length;
        $(".cs-search_result").html(totalCount + "/" + cardsCount);
    }

    async function updatePage(key = "*", isReplace = false) {
        if (key == "*" && $('#tabType').val() == "claim") {
            updateShowingResult(key);
            return;
        }

        const formData = new FormData();
        formData.append('pageType', $('#pageType').val());

        if(isReplace)
            formData.append('cardsCount', 0)
        else
            formData.append('cardsCount', $('#cardsCount').val())

        if ($('#pageType').val() == "main") {
            formData.append('menuCollection', $('#menuCollection').val());
            formData.append('menuRarity', $('#menuRarity').val())
            formData.append('menuColor', $('#menuColor').val())
            formData.append('menuSale', $('#menuSale').val())
            formData.append('menuBid', $('#menuBid').val());
        }
        else if ($('#pageType').val() == "claim") {
            formData.append('tabType', $('#tabType').val());
        }

        try {
            $('.cs-preloader').delay(10).fadeIn('slow'); //Show loading screen
            $('.cs-preloader span').html("Filtering...");

            const response = await fetch('index.php', {
                method: 'POST',
                body: formData
            });

            const html = await response.text();

            if ($('#pageType').val() == "claim") {
                $('.cs-isotop').append(html);
                $('.cs-isotop').isotope('reloadItems').isotope('layout');
                setTimeout(function () {
                    isotopInit();
                }, 1000);
            }
            else if ($('#pageType').val() == "main") {
                if (!isReplace)
                    $('#nft-list').append(html);
                else
                {
                    $('#nft-list').html(html);
                }
            }

        } catch (error) {
            console.error(error);
        }

        updateShowingResult(key);

        $('.cs-preloader').delay(10).fadeOut('slow'); //Show loading screen
    }

    //When scrolling reach up to bottom, load more nft infos
    $(window).scroll(async function () {
        if ($('.cs-preloader').css('display') != 'none')
            return;

        var filterElement = $('#tabType').val();

        console.log("***************tabType:", filterElement);
        if (filterElement == "*" && $('#tabType').val() == "claim")
            return;

        if ($(window).scrollTop() + window.innerHeight >= $(document).height() - 1) {
            var cardsCount = $('#nft-list').find(filterElement == "*" ? ".nft-card" : filterElement).toArray().length;
            $('#cardsCount').val(cardsCount);

            console.log("************scroll:", getTotalCount(filterElement), "/ ", cardsCount);

            if (cardsCount < getTotalCount(filterElement)) {
                updatePage(filterElement);
            }
            else
                updateShowingResult(filterElement);
        }
    });


    //**************Tab option for Claim/Reveal page****************** */
    $('.cs-isotop_filter ul').on('click', 'a', function () {
        var filterElement = $(this).attr('data-filter');

        $('#tabType').val(filterElement);

        var cardsCount = $('#nft-list').find(filterElement == "*" ? ".nft-card" : filterElement).toArray().length;
        $('#cardsCount').val(cardsCount);

        console.log(cardsCount, getTotalCount(filterElement))
        if ((getTotalCount(filterElement) > 0 && cardsCount == 0) || (cardsCount < getTotalCount(filterElement) && cardsCount % $('#numCardsPerPage').val() != 0)) {
            updatePage(filterElement);
        }
        else
            updateShowingResult(filterElement);
    });



    //**************Filter option for Marketplace page****************** */

    $('.form-check-label').on('click', function (event) {
        event.stopPropagation();
    });

    $('.form-check').on('click', function () {
        var id = $(this).attr("type");

        if (id == "collection") {
            var menuCollection = $(this).find(".form-check-label").text().trim();
            $('#menuCollection').val(menuCollection);
            updatePage("*", true);
        }
        else if (id == "rarity") {
            var menuRarity = $(this).find(".form-check-label").text().trim();
            $('#menuRarity').val(menuRarity);
            updatePage("*", true);
        }
        $(this).find(".form-check-input").prop("checked");
    });

    $('.form-check-input').on('click', function () {
        var id = $(this).attr("id");
        var checked = $(this).prop("checked") ? "checked" : "";

        if (id == "flexCheckDefault1") {
            $('#menuSale').val(checked);
            updatePage("*", true);
        }
        else if (id == "flexCheckDefault2") {
            $('#menuBid').val(checked);
            updatePage("*", true);
        }
    });

    $('.cs-color_item').on('click', function () {
        var menuColor = $(this).attr("id");
        $('#menuColor').val(menuColor);
        updatePage("*", true);
    });

})(jQuery); // End of use strict