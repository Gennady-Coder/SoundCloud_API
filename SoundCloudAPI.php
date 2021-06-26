<?php
use GuzzleHttp\Client;
require 'vendor/autoload.php';

class SoundCloudAPI
{
    const BASE_URI = 'https://api-v2.soundcloud.com/users/';
    const CLIENT_ID = 'gYkVE2E1CDaIAQpIpP1FfmFt427RgSsv';

    private static $users = array('103470313','19697305','3058637','95793893','102429008');

    //Подключение к базе данных и создание таблиц
    private static function dataBase(){
        $mysqli = new mysqli("localhost", "root", "pass");
        // Проверяем соединение
        if($mysqli->connect_error){
            die("ERROR: Ошибка подключения: " . $mysqli->connect_error);
        }
        // Создание базы данных с именем soundcloud
        $sql = "CREATE DATABASE soundcloud";
        if($mysqli->query($sql) === true){
            echo "База данных успешно создана";
        } else {
            echo "Ошибка создания базы данных $sql. " . $mysqli->error;
        }
        $mysqli = new mysqli("localhost", "root", "pass", 'soundcloud');
        $sql_table_artists = "CREATE TABLE media_artists(
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            full_name VARCHAR(50) NOT NULL,
            username VARCHAR(50) NOT NULL,
            city VARCHAR(30) NOT NULL,
            uri VARCHAR(50) NOT NULL, 
            user_id INT NOT NULL
        )";
        $sql_table_tracks = "CREATE TABLE media_tracks(
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(300) NOT NULL,
            genre VARCHAR(50) NOT NULL,
            uri VARCHAR(100) NOT NULL, 
            media_artist_id INT NOT NULL,
            track_id INT NOT NULL
        )";
        //Cоздаем таблицы
        if($mysqli->query($sql_table_artists)){
            echo "Таблица media_artists успешно создана.";
        } else{
            echo "ERROR: Не удалось выполнить $sql_table_artists. " . $mysqli->error;
        }
        if($mysqli->query($sql_table_tracks)){
            echo "Таблица media_tracks успешно создана.";
        } else{
            echo "ERROR: Не удалось выполнить $sql_table_tracks. " . $mysqli->error;
        }
        // закрываем соединение
        $mysqli->close();
    }


    private static function getUsers(){

        $mysqli = new mysqli("localhost", "root", "pass", 'soundcloud');
        $client = new Client(['base_uri' => self::BASE_URI]);

        foreach (self::$users as $user){
            $response = $client->request('GET', $user.'?client_id=gYkVE2E1CDaIAQpIpP1FfmFt427RgSsv&limit=20&offset=0&linked_partitioning=1&app_version=1624617819&app_locale=en');
            $body = json_decode($response->getBody());

            $full_name = $body->full_name;
            $user_name = $body->username;
            $city = $body->city;
            $uri = $body->uri;
            $user_id = $body->id;

            $sql = "INSERT INTO media_artists (full_name, username, city, uri, user_id) VALUES ('$full_name', '$user_name', '$city', '$uri', '$user_id')";
            if(!$mysqli->query($sql)){
                echo "ERROR: Не удалось выполнить $sql. " . $mysqli->error;
            }

        }
        $mysqli->close();
    }

    private static function getTracks(){

        $mysqli = new mysqli("localhost", "root", "pass", 'soundcloud');
        $client = new Client(['base_uri' => self::BASE_URI]);

        foreach (self::$users as $user){
            $response = $client->request('GET', $user.'/tracks?representation=&client_id=gYkVE2E1CDaIAQpIpP1FfmFt427RgSsv&limit=100&offset=0&linked_partitioning=1&app_version=1624617819&app_locale=en');
            $body = json_decode($response->getBody());

            foreach ($body->collection as $track){
                $title = $track->title;
                $code_match = array( '"', "'", '`');
                $title = str_replace($code_match, '', $title);
                $genre = $track->genre;
                $uri = $track->uri;
                $media_artist_id = $user;
                $track_id = $track->id;

                $sql = "INSERT INTO media_tracks (title, genre, uri, media_artist_id, track_id) VALUES ('$title', '$genre', '$uri', '$media_artist_id', '$track_id')";
                if(!$mysqli->query($sql)){
                    echo "ERROR: Не удалось выполнить $sql. " . $mysqli->error;
                }
            }

        }
        $mysqli->close();
    }

    public static function getAll(){

        self::dataBase();
        self::getUsers();
        self::getTracks();

        echo 'Артисты и их треки успешно добавлены в базу данных!';

    }

}

SoundCloudAPI::getAll();
