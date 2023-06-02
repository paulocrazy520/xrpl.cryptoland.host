(function ($) {
    'use strict';

    $(function () {
        var totalPages = $('#totalPages').val();

        if ($('#page').val() == 1)
            $(".btnPrev").prop("disabled", true);
        else
            $(".btnPrev").prop("disabled", false);


        if ($('#page').val() < totalPages)
            $(".btnNext").prop("disabled", false);
        else
            $(".btnNext").prop("disabled", true);

        var numTotalCards = $('#numTotalCards').val();
        var cards = $('#nft-list').find('.nft-card').toArray();
        var cardsIndex = Math.min(cards.length, $('#numCardsPerPage').val() * $('#page').val());
        $(".cs-search_result").html(numTotalCards + "/" + cardsIndex);
    });

    //When scrolling reach up to bottom, load more nft infos
    $(window).scroll(async function () {
        if ($('.cs-preloader').css('display') != 'none')
            return;

        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 1) {
            var totalPages = $('#totalPages').val();
            var page = parseInt($('#page').val());
            if (page < totalPages) {
                $('#page').val(page + 1);
                await loadMorePage();
            }
        }
    });

    /*=============================================================================*/
    /*---------------------Reload page rendering for main page --------------------*/
    /*=============================================================================*/
    async function loadMorePage(isPost = false) {
        if (isPost)
            $('#page').val(1);

        const formData = new FormData();

        formData.append('pageType', $('#pageType').val());
        formData.append('page', $('#page').val())

        if ($('#pageType').val() == "main") {
            formData.append('menuCollection', $('#menuCollection').val());
            formData.append('menuRarity', $('#menuRarity').val())
            formData.append('menuColor', $('#menuColor').val())
            formData.append('menuSale', $('#menuSale').val())
            formData.append('menuBid', $('#menuBid').val());
        }
        else if ($('#pageType').val() == "claim") {

        }
        
        try {
            $('.cs-preloader').delay(10).fadeIn('slow'); //Show loading screen

            const response = await fetch('index.php', {
                method: 'POST',
                body: formData
            });
            const html = await response.text();
            if (isPost)
                $('#nft-list').html(html);
            else {
                $('.cs-isotop').append(html);
                $('.cs-isotop').isotope('reloadItems').isotope('layout');

                setTimeout(function () {
                    isotopInit();
                }, 1000);
            }
        } catch (error) {
            console.error(error);
        }

        var numTotalCards = $('#numTotalCards').val();
        var cards = $('#nft-list').find('.nft-card').toArray();
        var cardsIndex = Math.min(cards.length, $('#numCardsPerPage').val() * $('#page').val());
        $(".cs-search_result").html(numTotalCards + "/" + cardsIndex);

        $('.cs-preloader').delay(10).fadeOut('slow'); //Show loading screen
    }

    $('.form-check-label').on('click', function (event) {
        event.stopPropagation();
    });

    $('.form-check').on('click', function () {
        var id = $(this).attr("type");

        if (id == "collection") {
            var menuCollection = $(this).find(".form-check-label").text().trim();
            $('#menuCollection').val(menuCollection);
            loadMorePage();
        }
        else if (id == "rarity") {
            var menuRarity = $(this).find(".form-check-label").text().trim();
            $('#menuRarity').val(menuRarity);
            loadMorePage();
        }
        $(this).find(".form-check-input").prop("checked");
    });

    $('.form-check-input').on('click', function () {
        var id = $(this).attr("id");
        var checked = $(this).prop("checked") ? "checked" : "";

        if (id == "flexCheckDefault1") {
            $('#menuSale').val(checked);
            loadMorePage();
        }
        else if (id == "flexCheckDefault2") {
            $('#menuBid').val(checked);
            loadMorePage();
        }
    });

    $('.cs-color_item').on('click', function () {
        var menuColor = $(this).attr("id");
        $('#menuColor').val(menuColor);
        loadMorePage();
    });
    ///////////////////////////////////////////////////////

})(jQuery); // End of use strict