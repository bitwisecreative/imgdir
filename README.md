# ImgDir
## Convenient Image Directory

***

#### Features

- Auto-generated Image Sizing
- Caching
- Admin Area
- PNG, JPG, GIF

#### Requirements

- Apache (with Rewrite)
- PHP 5.4+
- PHP GD Library

#### Notes

- Change the `ADMIN_PASSWORD` in `config.php`
- You can upload images manually to the `originals` folder
- GIF animations are not retained after resizing (TBD)

#### Image Modifiers (GET Params):

- **`w`** = width
    - _auto-calculated at ratio if only `h` is provided_
- **`h`** = height
    - _auto-calculated at ratio if only `w` is provided_
- **`r`** = resize type
    - _only necessary when both `w` and `h` supplied_
	- `f` (fit) (default)
		- _fits the entire image into the size_
	- `c` (crop)
		- _center-crops the image_
- **`b`** = background
	- pass hex color (e.g., `ff23ba`) (default is white for jpg, transparent for png)
		- _only applies with fit (`r=f`)_
- **`debug`** = debug mode
    - _if is set, will always recache and display text (instead of fallback images) on failure_

- Program attempts to gracefully fail and display an image if possible
- Displays `assets/default.png` as fallback

#### Examples:

- `/__test_landscape.png?w=300`
    - resizes image to 300px wide, keeping the aspect ratio

- `/__test_landscape.png?w=300&h=300`
    - resizes image to 300px wide and 300px tall, fitting the aspect ratio with a white background fill

- `/__test_landscape.png?w=300&h=300&r=f&b=000000`
    - resizes image to 300px wide and 300px tall, fitting the aspect ratio with a black background fill

- `/__test_landscape.png?w=80&h=80&r=c`
    - resizes image to 80px wide and 80px tall, center-cropping the contents