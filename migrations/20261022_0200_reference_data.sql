-- Reference data: booking workflow, opening hours, editable copy, starter catalogue.

INSERT INTO booking_statuses (id, code, name, color, text_color, sort, counts_as_booked, is_default, active) VALUES
  (1, 'new',       'New',       '#d6336c', '#ffffff', 10, 1, 1, 1),
  (2, 'confirmed', 'Confirmed', '#1c7a4b', '#ffffff', 20, 1, 0, 1),
  (3, 'done',      'Done',      '#5a1d4e', '#ffffff', 30, 0, 0, 1),
  (4, 'cancelled', 'Cancelled', '#6f5d6b', '#ffffff', 40, 0, 0, 1),
  (5, 'no_show',   'No show',   '#b3261e', '#ffffff', 50, 0, 0, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO settings (`key`, `value`, `label`) VALUES
  ('salon_name',           'Zuri Beauty Lounge',                 'Salon name shown across the site'),
  ('whatsapp',             '254700000000',                       'WhatsApp number in international format'),
  ('phone',                '0700 000 000',                       'Phone number displayed on the site'),
  ('email',                'hello@zuribeauty.co.ke',             'Public email address'),
  ('address',              '2nd Floor, Ruaka Mall, Ruaka',      'Address'),
  ('map_query',            'Ruaka Mall, Ruaka',     'Google Maps search text'),
  ('social_instagram',     '@zuribeautylounge',                  'Instagram handle'),
  ('social_tiktok',        '@zuribeautylounge',                  'TikTok handle'),
  ('meta_description',     'Braids, silk press, locs, nails, makeup and spa treatments in Nairobi. Book online in under a minute and pay by M-Pesa.', 'Search engine description'),
  ('capacity',             '2',                                  'Bookings allowed per time slot'),
  ('slot_step',            '30',                                 'Minutes between bookable times'),
  ('max_days_ahead',       '90',                                 'How far ahead clients can book'),
  ('lead_time_minutes',    '60',                                 'Minimum notice before a slot'),
  ('deposit_note',         'KES 500 by M-Pesa for braids and bridal bookings.', 'Deposit line in the booking panel'),
  ('booking_success_text', 'Booking received. We will confirm on WhatsApp shortly.', 'Message after a client books'),
  ('sms_enabled',          '0',                                  'Send booking SMS'),
  ('sms_notify_number',    '',                                   'Salon number that receives booking SMS'),
  ('sms_shortcode',        '',                                   'Sender ID or shortcode'),
  ('sms_endpoint',         'https://sms.textsms.co.ke/api/services/sendsms/', 'SMS gateway endpoint'),
  ('sms_method',           'GET',                                'SMS gateway HTTP method'),
  ('sms_param_map',        'apikey:apikey,partnerID:partnerID,message:message,shortcode:shortcode,mobile:mobile', 'SMS gateway parameter names'),
  ('sms_template',         'New booking {ref}: {name}, {service}, {date} {time}. Call {phone}', 'Booking SMS text'),
  ('sms_admin_notify',     '1',                                  'Also notify opted-in admins')
ON DUPLICATE KEY UPDATE `key` = VALUES(`key`);

INSERT INTO opening_hours (day_of_week, opens, closes, closed) VALUES
  (0, '10:00:00', '18:00:00', 0),
  (1, '08:00:00', '20:00:00', 0),
  (2, '08:00:00', '20:00:00', 0),
  (3, '08:00:00', '20:00:00', 0),
  (4, '08:00:00', '20:00:00', 0),
  (5, '08:00:00', '20:00:00', 0),
  (6, '08:00:00', '21:00:00', 0);

INSERT INTO site_content (`key`, `value`, label, sort) VALUES
  ('hero_title',       'Hair and beauty that fits your day, not the other way round.', 'Hero heading', 10),
  ('hero_text',        'Braids, silk press, locs, nails and makeup in Westlands, Nairobi. Pick a service, choose a time, and we will confirm on WhatsApp.', 'Hero paragraph', 20),
  ('hero_cta_primary', 'Book an appointment', 'Hero main button', 30),
  ('hero_cta_secondary','See prices', 'Hero second button', 40),
  ('trust_line_1',     '4.9 from 600+ reviews', 'Hero trust line 1', 50),
  ('trust_line_2',     'Pay by M-Pesa or cash', 'Hero trust line 2', 60),
  ('trust_line_3',     'Walk-ins welcome', 'Hero trust line 3', 70),
  ('services_intro',   'Prices are in Kenya shillings. Final price depends on hair length and thickness; we confirm before we start.', 'Services intro', 80),
  ('services_note',    'Bringing your own hair extensions? Tell us when you book and we will adjust the price.', 'Services footnote', 90),
  ('why_heading',      'Why clients come back', 'Why section heading', 100),
  ('reviews_heading',  'What our clients say', 'Reviews section heading', 110),
  ('book_heading',     'Book your appointment', 'Booking section heading', 120),
  ('book_text',        'Send your request and we will confirm by WhatsApp or phone within 30 minutes during opening hours.', 'Booking section text', 130),
  ('visit_heading',    'Find us', 'Visit section heading', 140),
  ('visit_extra',      'Free for clients in the basement', 'Parking line', 150),
  ('map_caption',      'Next to Sarit Centre, lift to the 2nd floor', 'Map caption', 160)
ON DUPLICATE KEY UPDATE `key` = VALUES(`key`);

INSERT INTO site_blocks (block_type, title, body, icon, sort, active) VALUES
  ('why',  'On time, every time', 'Book a slot and we keep it. No sitting around for hours waiting your turn.', 'tt', 10, 1),
  ('why',  'Skilled with every texture', 'Our stylists are trained in natural hair care, from 4C twist-outs to sleek silk presses.', 'tx', 20, 1),
  ('why',  'Clean and safe', 'Sterilised tools, fresh towels for every client, and products we are happy to name.', 'cs', 30, 1),
  ('rule', 'Deposit', 'KES 500 by M-Pesa for braids and bridal bookings.', 'dp', 40, 1),
  ('rule', 'Running late?', 'Message us. We hold your slot for 15 minutes.', 'lt', 50, 1),
  ('rule', 'Cancelling?', 'Free up to 12 hours before your appointment.', 'cn', 60, 1);

INSERT INTO reviews (author, location, rating, body, sort, active) VALUES
  ('Wanjiru',  'Kilimani',  5, 'Knotless braids done in four hours and they were neat and light. I was in and out before my lunch meeting.', 10, 1),
  ('Achieng',  'Lavington', 5, 'Booked through WhatsApp on Friday night and got a Saturday morning slot. Paid by M-Pesa, no stress.', 20, 1),
  ('Faith',    'Westlands', 5, 'My wedding makeup lasted from the church to the late reception. The whole bridal party was happy.', 30, 1);

INSERT INTO service_categories (id, name, blurb, sort, active) VALUES
  (1, 'Hair',   'Braids, locs, silk press and treatments', 10, 1),
  (2, 'Nails',  'Manicures, gel, acrylic and pedicure',    20, 1),
  (3, 'Makeup', 'Day, evening and bridal',                 30, 1),
  (4, 'Spa',    'Facials, massage and brow shaping',       40, 1);

INSERT INTO services (category_id, name, duration, duration_min, price, sort, active) VALUES
  (1, 'Knotless braids',               '4 to 6 hours',            300, 3500, 10, 1),
  (1, 'Box braids',                    '4 to 6 hours',            300, 3000, 20, 1),
  (1, 'Cornrows',                      '1 to 3 hours',            150,  800, 30, 1),
  (1, 'Silk press',                    '2 hours',                 120, 2500, 40, 1),
  (1, 'Wash, blow-dry and style',      '1 hour',                   60, 1200, 50, 1),
  (1, 'Loc retwist',                   '2 hours',                  120, 1500, 60, 1),
  (1, 'Hair treatment',                '1 hour',                    60, 1800, 70, 1),
  (2, 'Classic manicure',              '45 min',                    45,  800, 80, 1),
  (2, 'Gel polish',                    '1 hour',                     60, 1500, 90, 1),
  (2, 'Acrylic full set',              '2 hours',                    120, 2500, 100, 1),
  (2, 'Pedicure',                      '1 hour',                      60, 1200, 110, 1),
  (3, 'Day makeup',                    '1 hour',                       60, 2500, 120, 1),
  (3, 'Evening glam',                  '1.5 hours',                    90, 3500, 130, 1),
  (3, 'Bridal makeup and trial',       '3 hours',                     180, 12000, 140, 1),
  (4, 'Express facial',                '45 min',                       45, 2000, 150, 1),
  (4, 'Back and shoulder massage',     '30 min',                       30, 1800, 160, 1),
  (4, 'Eyebrow shaping',               '20 min',                       20,  500, 170, 1);

-- @down
DELETE FROM services WHERE id > 0;
DELETE FROM service_categories WHERE id > 0;
DELETE FROM reviews WHERE id > 0;
DELETE FROM site_blocks WHERE id > 0;
DELETE FROM site_content WHERE `key` IN ('hero_title','hero_text','hero_cta_primary','hero_cta_secondary',
  'trust_line_1','trust_line_2','trust_line_3','services_intro','services_note','why_heading','reviews_heading',
  'book_heading','book_text','visit_heading','visit_extra','map_caption');
DELETE FROM settings WHERE `key` IN ('salon_name','whatsapp','phone','email','address','map_query',
  'social_instagram','social_tiktok','meta_description','capacity','slot_step','max_days_ahead',
  'lead_time_minutes','deposit_note','booking_success_text','sms_enabled','sms_notify_number','sms_shortcode',
  'sms_endpoint','sms_method','sms_param_map','sms_template','sms_admin_notify');
DELETE FROM opening_hours WHERE day_of_week BETWEEN 0 AND 6;
DELETE FROM booking_statuses WHERE code IN ('new','confirmed','done','cancelled','no_show');
