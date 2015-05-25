Field: Image upload
==============

Github repo: https://github.com/deuxhuithuit/image_upload


## 1 About ##

A specialized version of the classic Upload field for images: 

- it accepts only images: bmp, jpg, jpeg, png, gif and svg.
- optional, set a minimum width and / or height. If 0 or empty, no minimum limit will exist.
- optional, set a maximum width and / or height. If 0 or empty, no maximum resize limit will exist.
- optional, it will create unique filenames.

**NB:** The resize takes places upon save, if needed, no matter new or edited entry.

**NB 2:** SVG support requires Symphony 2.6.3 or [this commit](https://github.com/symphonycms/symphony-2/commit/75ce918aee524af41cf17843dbee9f1e87d6c577).

## 2 Installation ##

1. Upload the `image_upload` folder found in this archive to your Symphony `extensions` folder.    
2. Enable it by selecting `Image upload` under `System -> Extensions`, choose Enable from the with-selected menu, then click Apply.
3. You can now add `Image upload` field to your sections.
