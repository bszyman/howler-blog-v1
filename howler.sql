-- phpMyAdmin SQL Dump
-- version 4.9.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Mar 20, 2022 at 03:38 AM
-- Server version: 5.7.26
-- PHP Version: 7.4.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `howler`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
                             `id` bigint(20) NOT NULL,
                             `uuid` varchar(60) NOT NULL,
                             `title` varchar(120) NOT NULL,
                             `description` varchar(500) NOT NULL,
                             `url` varchar(100) NOT NULL,
                             `image_file` varchar(100) NOT NULL,
                             `public` tinyint(1) NOT NULL DEFAULT '0',
                             `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                             `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE `friends` (
                           `id` bigint(20) NOT NULL,
                           `uuid` varchar(60) NOT NULL,
                           `name` varchar(50) NOT NULL,
                           `url` varchar(100) NOT NULL,
                           `image_file` varchar(100) NOT NULL,
                           `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                           `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
                         `id` bigint(20) NOT NULL,
                         `post_text` varchar(500) NOT NULL,
                         `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                         `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                         `user` bigint(20) NOT NULL,
                         `in_reply_to` bigint(20) NOT NULL,
                         `quote_from` varchar(100) NOT NULL,
                         `quote_contents` text NOT NULL,
                         `published` tinyint(1) NOT NULL DEFAULT '0',
                         `public` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site`
--

CREATE TABLE `site` (
                        `id` int(11) NOT NULL,
                        `site_name` varchar(200) NOT NULL,
                        `profile_pic` varchar(100) NOT NULL,
                        `bio_name` varchar(50) DEFAULT NULL,
                        `bio` varchar(500) DEFAULT NULL,
                        `location` varchar(60) DEFAULT NULL,
                        `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        `include_feed` tinyint(1) NOT NULL DEFAULT '1',
                        `include_bookmarks` tinyint(1) NOT NULL DEFAULT '1',
                        `include_following` tinyint(1) NOT NULL DEFAULT '0',
                        `allow_rss_feed` tinyint(1) NOT NULL DEFAULT '1',
                        `allow_atom_feed` tinyint(1) NOT NULL DEFAULT '1',
                        `social_flickr` varchar(300) DEFAULT NULL,
                        `social_instagram` varchar(300) DEFAULT NULL,
                        `social_soundcloud` varchar(300) DEFAULT NULL,
                        `social_unsplash` varchar(300) DEFAULT NULL,
                        `social_vimeo` varchar(300) DEFAULT NULL,
                        `social_website` varchar(300) NOT NULL,
                        `social_youtube` varchar(300) DEFAULT NULL,
                        `social_bitbucket` varchar(300) NOT NULL,
                        `social_github` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
                         `id` bigint(20) NOT NULL,
                         `email` varchar(50) NOT NULL,
                         `full_name` varchar(50) NOT NULL,
                         `enabled` tinyint(1) NOT NULL DEFAULT '0',
                         `administrator` tinyint(1) NOT NULL DEFAULT '0',
                         `password` varchar(255) DEFAULT NULL,
                         `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                         `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
    ADD PRIMARY KEY (`id`);

--
-- Indexes for table `friends`
--
ALTER TABLE `friends`
    ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `site`
--
ALTER TABLE `site`
    ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
    ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
    MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `friends`
--
ALTER TABLE `friends`
    MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
    MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `site`
--
ALTER TABLE `site`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
    MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
