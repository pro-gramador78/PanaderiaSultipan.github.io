let productos = [];

function agregarLinea() {
  const select = document.getElementById("producto_select");
  const cantidad = parseInt(document.getElementById("cantidad").value);
  const id = parseInt(select.value);
  const nombre = select.options[select.selectedIndex].text.split(" ($")[0];
  const precio = parseFloat(select.options[select.selectedIndex].dataset.precio);

  if (!id || cantidad <= 0) {
    alert("Selecciona un producto y cantidad válida");
    return;
  }

  // Verificar si ya está en la tabla
  const existe = productos.find(p => p.id === id);
  if (existe) {
    existe.cantidad += cantidad;
  } else {
    productos.push({ id, nombre, cantidad, precio });
  }

  actualizarTabla();
  document.getElementById("cantidad").value = 1;
}

function actualizarTabla() {
  const tbody = document.querySelector("#tabla_detalle tbody");
  tbody.innerHTML = "";

  let total = 0;

  productos.forEach((prod, index) => {
    const subtotal = prod.precio * prod.cantidad;
    total += subtotal;

    const fila = `
      <tr>
        <td>${prod.nombre}</td>
        <td>$${prod.precio.toFixed(2)}</td>
        <td>${prod.cantidad}</td>
        <td>$${subtotal.toFixed(2)}</td>
        <td><button type="button" onclick="eliminarLinea(${index})">Eliminar</button></td>
      </tr>
    `;
    tbody.innerHTML += fila;
  });

  document.getElementById("total").innerText = total.toFixed(2);
  document.getElementById("total_hidden").value = total.toFixed(2);
  actualizarVuelto();
  document.getElementById("detalles_hidden").value = JSON.stringify(productos);
}

function eliminarLinea(index) {
  productos.splice(index, 1);
  actualizarTabla();
}

function actualizarVuelto() {
  const recibido = parseFloat(document.getElementById("recibido").value);
  const total = parseFloat(document.getElementById("total").innerText);
  const vuelto = recibido - total;
  document.getElementById("vuelto").innerText = vuelto >= 0 ? vuelto.toFixed(2) : "0.00";
  document.getElementById("vuelto_hidden").value = vuelto.toFixed(2);
}

function prepararEnvio() {
  if (productos.length === 0) {
    alert("Agrega al menos un producto.");
    return false;
  }
  return true;
}
