let restrictCopy = function () {
    document.ondragstart = noselect;
// запрет на перетаскивание
    document.onselectstart = noselect;
// запрет на выделение элементов страницы
    document.oncontextmenu = noselect;

// запрет на выведение контекстного меню
    function noselect() {
        return false;
    }

    function strictRightClick (){
        document.onmousedown = function (e) {
            e = e || window.event;
            var b = (e.which ? (e.which < 2) : (e.button < 2));
            if (!b) return false;
        };
    }
    function slyLink() {


        var istS = `Первоисточник:`; // Слово заключать в кавычки

        var copyR = document.location.host; // Название ресурса

        var body_element = document.getElementsByTagName(`body`)[0];

        var choose;

        choose = window.getSelection();

        var myLink = document.location.href;

        var authorLink = `«»` + istS + `` + `«»`+myLink+`»``»` + copyR;

        var copytext = choose + authorLink;

        var addDiv = document.createElement(`div`);

        addDiv.style.position = `absolute`;

        addDiv.style.left = `-99999px`;

        body_element.appendChild(addDiv);

        addDiv.innerHTML = copytext;

        choose.selectAllChildren(addDiv);

        window.setTimeout(function() {

            body_element.removeChild(addDiv);

        },0);

    }

    document.oncopy = function (e) {
        return false
    };
    // запрет правой кнопки мышки

    document.oncontextmenu = function (e) {
        return false
    };



    // запрет выделения мышкой и комбинаций клавиш Ctrl + A, Ctrl + U и Ctrl + S
    function preventSelection(e) {
        var preventSelection = false;

        function addHandler(e, event, handler) {
            if (e.attachEvent) e.attachEvent('on' + event, handler);
            else if (e.addEventListener) e.addEventListener(event, handler, false);
        }

        function removeSelection() {
            if (window.getSelection) {
                window.getSelection().removeAllRanges();
            } else if (document.selection && document.selection.clear)
                document.selection.clear();
        }

// запрещаем выделять текст мышкой
        addHandler(e, 'mousemove', function () {
            if (preventSelection) removeSelection();
        });
        addHandler(e, 'mousedown', function (e) {
            e = e || window.event;
            var sender = e.target || e.srcElement;
            preventSelection = !sender.tagName.match(/INPUT|TEXTAREA/i);
        });

// запрещаем нажатие клавиш Ctrl + A, Ctrl + U и Ctrl + S
        function killCtrlA(e) {
            e = e || window.e;
            var sender = e.target || e.srcElement;
            if (sender.tagName.match(/INPUT|TEXTAREA/i)) return;
            var key = e.keyCode || e.which;
            if ((e.ctrlKey && key == 'U'.charCodeAt(0)) || (e.ctrlKey && key == 'A'.charCodeAt(0)) || (e.ctrlKey && key == 'S'.charCodeAt(0)) || (e.ctrlKey && key == 'P'.charCodeAt(0)))
            {
                removeSelection();
                if(e.preventDefault) e.preventDefault();
                else e.returnValue = false;
            }
        }

        addHandler(e, 'keydown', killCtrlA);
        addHandler(e, 'keyup', killCtrlA);
    }

    preventSelection(document);

}
