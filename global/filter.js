(function ($) {
    'use strict';

    let tabTypeIdArray = { "*": "total", ".unclaimed": "unclaimed", ".unrevealed": "unrevealed", ".revealed": "revealed" };

    function getTotalCount(key = "*") {
        return Math.ceil($("#" + tabTypeIdArray[key] + "Count").val());
    }

    function updateShowingResult(key = "*") {
        var totalCount = $("#" + tabTypeIdArray[key] + "Count").val();
        var cardsCount = $('#nft-list').find(key == "*" ? ".nft-card" : key).toArray().length;
        $(".cs-search_result").html(totalCount + "/" + cardsCount);
    }

    async function loadMorePage(key = "*") {
        if (key == "*")
            return;

        const formData = new FormData();

        formData.append('pageType', $('#pageType').val());
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

            const response = await fetch('index.php', {
                method: 'POST',
                body: formData
            });
            const html = await response.text();
            $('.cs-isotop').append(html);
            $('.cs-isotop').isotope('reloadItems').isotope('layout');

            setTimeout(function () {
                isotopInit();
            }, 1000);

        } catch (error) {
            console.error(error);
        }

        updateShowingResult(key);

        $('.cs-preloader').delay(10).fadeOut('slow'); //Show loading screen
    }

    $(function () {
        updateShowingResult();
    });

    //When scrolling reach up to bottom, load more nft infos
    $(window).scroll(async function () {
        if ($('.cs-preloader').css('display') != 'none')
            return;

        var filterElement = $('#tabType').val();
        console.log(filterElement);

        if (filterElement == "*")
            return;
 
        console.log(filterElement);
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 1) {
            var cardsCount = $('#nft-list').find(filterElement == "*" ? ".nft-card" : filterElement).toArray().length;
            $('#cardsCount').val(cardsCount);

            console.log(cardsCount, getTotalCount(filterElement),)

            if (cardsCount < getTotalCount(filterElement)) {
                loadMorePage(filterElement);
            }
            else
                updateShowingResult(filterElement);
        }
    });

    $('.cs-isotop_filter ul').on('click', 'a', function () {
        var filterElement = $(this).attr('data-filter');
        
        $('#tabType').val(filterElement);

        var cardsCount = $('#nft-list').find(filterElement == "*" ? ".nft-card" : filterElement).toArray().length;
        $('#cardsCount').val(cardsCount);

        console.log(cardsCount, getTotalCount(filterElement))
        if ((getTotalCount(filterElement) > 0 && cardsCount == 0) || (cardsCount < getTotalCount(filterElement) && cardsCount % $('#numCardsPerPage').val() != 0)) {
            loadMorePage(filterElement);
        }
        else
            updateShowingResult(filterElement);
    });


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