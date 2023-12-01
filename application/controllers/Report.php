<?php

class Report extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        is_logged_in();
    }
    public function index()
    {
        $data['judul'] = "Laporan Harian";
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['menu'] = $this->db->get('event')->result_array();

        $this->form_validation->set_rules('nama_event', 'Event', 'required');
        $this->form_validation->set_rules('deskripsi', 'Event', 'required');

        if ($this->form_validation->run() == false) {
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('report/index', $data);
            $this->load->view('templates/footer');
        } else {
            // Simpan data ke database
            $this->db->insert('event', [
                'nama_event' => $this->input->post('nama_event'),
                'deskripsi' => $this->input->post('deskripsi')
            ]);

            // Simpan data event dalam sesi
            $event_data = [
                'nama_event' => $this->input->post('nama_event'),
                'deskripsi' => $this->input->post('deskripsi')
            ];

            $this->session->set_userdata('event_data', $event_data);
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Event Telah ditambahkan!</div>');
            redirect('event');
        }
    }

    public function wajib()
    {
        $this->form_validation->set_rules('judul', 'Judul', 'required|trim', [
            'required' => 'Masukkan judul dengan benar!'
        ]);
        $this->form_validation->set_rules('tanggal', 'Tanggal', 'required', [
            'required' => 'Masukkan tanggal dengan benar!'
        ]);
        $this->form_validation->set_rules('deskripsi', 'Deskripsi', 'required|trim', [
            'required' => 'Masukkan keterangan dengan jelas!',
            'valid_email' => 'Masukkan Email yang Sesuai!',
            'min_length' => 'Masukan laporan dengan detail!'
        ]);

        if ($this->form_validation->run() == false) {
            $data['judul'] = 'Formulir Laporan Wajib';
            $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
            $error_message = validation_errors();
            $error_message = validation_errors();
            if (!empty($error_message)) {
                // Hanya set flash data jika ada pesan kesalahan
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">' . $error_message . '</div>');
            }
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('report/wajib', $data);
            $this->load->view('templates/footer');
        } else {
            // Konfigurasi upload gambar
            $config['upload_path']   = 'assets/img/report/wajib/';
            $config['allowed_types'] = 'jpg|png';
            $config['max_size']      = 5024; // in kilobytes

            $this->load->library('upload', $config);

            $file_count = count(glob($config['upload_path'] . '/*'));

            if ($this->upload->do_upload('image')) {
                // Jika upload berhasil, simpan data ke database
                $upload_data = $this->upload->data();
                $original_name = $upload_data['file_name'];

                // Format nomor urutan ke dalam format '001'
                $file_count++;
                $sequence_number = sprintf("%03d", $file_count);

                // Gabungkan nomor urutan dengan nama file asli
                $new_file_name = 'laporanwajib_' . $sequence_number . '_' . $original_name;

                // Ganti nama file
                rename($config['upload_path'] . '/' . $original_name, $config['upload_path'] . '/' . $new_file_name);
                $this->db->insert('laporanwajib', [
                    'nopeg' => $this->input->post('nopeg'),
                    'nama' => $this->input->post('nama'),
                    'judul' => $this->input->post('judul'),
                    'deskripsi' => htmlspecialchars($this->input->post('deskripsi')),
                    'tanggal' => $this->input->post('tanggal'),
                    'komentar' => $this->input->post('komentar'),
                    'image' => $new_file_name,
                    'date_created' => time()
                ]);
                $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Laporan harian berhasil dikirim!</div>');
                redirect('report');
            } else {
                // Jika upload gagal, tangani kesalahan upload
                $error_message = $this->upload->display_errors();
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">' . $error_message . '</div>');
                redirect('report/wajib'); // Redirect kembali ke halaman formulir dengan pesan kesalahan
            }
        }
    }
}
