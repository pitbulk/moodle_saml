var printf = function(string){
    if (arguments.length <2) { return string; }
        for (var i=1; i<arguments.length; i++)
        { string = string.replace(/%d/, arguments[i]); }
        return string;
}

function addNewField(area,field, type) {
    var field_area = document.getElementById(area);
    var total = document.getElementById('new_' + type + 's_total');
    var count = parseInt(total.value) + 1;
    var field_name = "new_" + type;

    var select = document.getElementById('new' + type + '_select');
    var new_select = select.cloneNode(true);
    
    total.value = count;

    if (document.createElement) {
        var tr = document.createElement("tr");
        if (type === 'course') {
            for ($i = 0; $i < 3; $i++) {
                var td$i = document.createElement("td");
                var input$i = document.createElement("input");
                tr.appendChild(td$i);
                input$i.name = field_name + count + "[]";
                switch ($i) {
                    case 0:
                        new_select.name = field_name + count + "[]";
                        td$i.style.paddingLeft = "38px";
                        td$i.colSpan = "2";
                        td$i.appendChild(new_select);
                        break;
                    default:
                        td$i.appendChild(input$i);
                        break;
                }
            }
        }
        if (type === 'role') {
            for ($i = 0; $i < 2; $i++) {
                var td$i = document.createElement("td");
                var input$i = document.createElement("input");
                tr.appendChild(td$i);
                input$i.name = field_name + count + "[]";
                switch ($i) {
                    case 0:
                        new_select.name = field_name + count + "[]";
                        td$i.style.paddingLeft = "38px";
                        td$i.colSpan = "2";
                        td$i.appendChild(new_select);
                        break;
                    default:
                        td$i.appendChild(input$i);
                        break;
                }
            }
        }
        field_area.appendChild(tr);
    }
}
