<?php
include 'koneksi.php';

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan.'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tampilan Data</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>
<body>
<div class="container-fluid mt-5">
    <h2 class="text-center mb-4">Form Usulan Kegiatan</h2>

    <!-- Daftar data usulan -->
    <h4 class="mt-5">Data Usulan Kegiatan:</h4>
    <table class="table mt-3" id="dataTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal Usulan</th>
                <th>Nama Kegiatan</th>
                <th>Nama Peminjam</th>
                <th>Surat Peminjaman</th>
                <th>Waktu Mulai</th>
                <th>Waktu Selesai</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM peminjaman_advanced";
            $result = $conn->query($sql);
            $no = 0;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $no++;
                    echo "<tr>
                            <th scope='row'>" . $no . "</th>
                            <td>" . $row['tanggal_usulan'] . "</td>
                            <td>" . $row['nama_kegiatan'] . "</td>
                            <td>" . $row['nama_peminjam'] . "</td>
                            <td><a href='uploads/" . $row['surat_peminjaman'] . "' target='_blank'>" . $row['surat_peminjaman'] . "</a></td>
                            <td>" . $row['waktu_mulai'] . "</td>
                            <td>" . $row['waktu_selesai'] . "</td>
                        </tr>";
                }
            } else {
                echo "<tr><td colspan='8'>Tidak ada data.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Buttons Conversion + Email MIKA -->
    <section class="text-center mt-4">
        <button type="button" class="btn btn-success" id="convertXlsBtn">Convert XLSX</button>
        <button type="button" class="btn btn-danger" id="convertPdfBtn">Convert PDF</button>
        <button type="button" class="btn btn-warning" id="email" style="color: white;">Email</button>
    </section>

    <!-- Popup Overlay untuk opsi pengiriman email -->
    <div id="emailPopup" class="overlay" style="display: none;">
        <div class="popup">
            <h2>Select Email Option</h2>
            <a class="close">&times;</a>
            <div class="modal-body">
                <button id="emailXlsxBtn" class="btn btn-success">Email XLSX</button>
                <button id="emailPdfBtn" class="btn btn-danger">Email PDF</button>
            </div>
        </div>
    </div>  

    <!-- STYLE OVERLAY -->
    <style>
        /* The popup overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* The popup content */
        .popup {
            background: #fff;
            padding: 20px;
            width: 300px;
            border-radius: 10px;
            text-align: center;
            position: relative;
        }

        /* Close button (X) */
        .popup .close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            text-decoration: none;
            color: #333;
            cursor: pointer;
        }

        .popup h2 {
            margin-top: 0;
            font-size: 20px;
        }

        .popup .btn {
            margin: 10px 0;
            width: 100%;
        }
    </style>

    <!-- SCRIPT OVERLAY -->
    <script>
        // Show the popup when the Email button is clicked
        document.getElementById("email").addEventListener("click", function () {
            document.getElementById("emailPopup").style.display = "flex";
        });

        // Hide the popup when the close button is clicked
        document.querySelector(".popup .close").addEventListener("click", function () {
            document.getElementById("emailPopup").style.display = "none";
        });

        // Optionally, hide the popup if you click outside the popup content
        window.addEventListener("click", function (event) {
            var popup = document.getElementById("emailPopup");
            if (event.target == popup) {
                popup.style.display = "none";
            }
        });
    </script>

    <!-- Convert table to XLSX and PDF, and Email Logic -->
    <script>
        // Convert table to PDF
        $('#convertPdfBtn').click(function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // AutoTable plugin to convert the HTML table
            doc.autoTable({
                html: '#dataTable',
                startY: 20,
                theme: 'grid',
                headStyles: { fillColor: [0, 123, 255] }, // Bootstrap's primary color
            });

            // Save the PDF
            doc.save('data-list.pdf');
        });

        // Convert table to XLSX
        $('#convertXlsBtn').click(function() {
            // Use SheetJS to convert the table to XLSX format
            var wb = XLSX.utils.table_to_book(document.getElementById('dataTable'), {sheet: "Data"});
            XLSX.writeFile(wb, 'data-list.xlsx');
        });
// kirim email ke dosen
        document.getElementById('emailXlsxBtn').addEventListener('click', function() {
            sendEmail('xlsx');
        });

        document.getElementById('emailPdfBtn').addEventListener('click', function() {
            sendEmail('pdf');
        });

        function sendEmail(format) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "send_email.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE) {
                    if (this.status === 200) {
                        alert(this.responseText); // Menampilkan respon dari server
                    } else {
                        alert("Terjadi kesalahan. Email gagal dikirim.");
                    }
                }
            };
            xhr.send("format=" + format); // Mengirim format file ke server (xlsx/pdf)
        }
    </script>

</body>
</html>