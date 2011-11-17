Field: Image upload
==============

A specialized field for image uploads.

* Version: 1.1.3
* Build Date: 2011-11-17
* Authors:
	- [Xander Group](http://www.xandergroup.ro)
	- Vlad Ghita
* Requirements:
	- Symphony 2.0 or above
	- If using maximum width / height, [JIT](https://github.com/symphonycms/jit_image_manipulation) is required

Thank you all other Symphony & Extensions developers for your inspirational work.



# 1 About #

A specialized version of the classic Upload field for images: 

- it accepts only images: bmp, jpg, jpeg, png and gif.
- optional, set a minimum width and / or height. If 0 or empty, no minimum limit will exist.
- optional, set a maximum width and / or height. If 0 or empty, no maximum resize limit will exist.
- optional, it will create unique filenames.

**NB:** The resize takes places upon save, no matter new entry or edit entry.



# 2 Installation #

1. Upload the `image_upload` folder found in this archive to your Symphony `extensions` folder.    
2. Enable it by selecting `Field: Image upload` under `System -> Extensions`, choose Enable from the with-selected menu, then click Apply.
3. You can now add `Image upload` field to your sections.




# 3 Compatibility #

         Symphony | Field: Image Upload
------------------|----------------
      2.0 — *     | [latest](https://github.com/vlad-ghita/image_upload)




# 4 Changelog #

- 1.1.3 : 17 nov 2011
    * Refined image dimension maximum limits discovery. It now properly resizes images so the result fits both max_width and max_height.
	* Increased desired image quality from 80 to 85.

- 1.1.2 : 17 nov 2011
    * Added missing `max_width` and `max_height` table columns.

- 1.1.1 : 17 nov 2011
    * Removed code for Imagemagick.

- 1.1 : 16 nov 2011
    * Added support for maximum width / height with smart resize using JIT mode 1.

- 1.0.1 : 16 nov 2011
    * Typo fix.

- 1.0 : 15 nov 2011
    * Initial release