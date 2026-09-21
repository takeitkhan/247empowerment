# Generic Style Control

A reusable React component and utility for implementing copy/paste styles functionality in any block.

## Overview

This package provides two main utilities:

1. **`createStyleClipboard`** - Factory function to create clipboard instances
2. **`GenericStyleControl`** - React component for UI (copy/paste buttons)

Located in: `src/inc/components/GenericStyleControl/`

**Files:**

- `index.js` - React component and exports
- `clipboard.js` - createStyleClipboard factory function
- `README.md` - Documentation (this file)

## Quick Start

### Step 1: Set up clipboard in your block component

```javascript
import GenericStyleControl, {
	createStyleClipboard,
} from "$Inc/components/GenericStyleControl";

// Define which attributes to copy/paste
const myClipboard = createStyleClipboard("ub_myblock_copied_style", [
	"attribute1",
	"attribute2",
	"borderRadius",
	"className",
]);

function MyBlockStyleControl({ attributes, setAttributes }) {
	return (
		<GenericStyleControl
			attributes={attributes}
			setAttributes={setAttributes}
			clipboard={myClipboard}
			blockName="My Block"
		/>
	);
}

export default MyBlockStyleControl;
```

### Step 2: Add to your block-controls.js

```javascript
import MyBlockStyleControl from "./components/MyBlockStyleControl";

function BlockControls(props) {
	const { attributes, setAttributes } = props;

	return (
		<>
			{/* Other controls... */}
			<MyBlockStyleControl
				attributes={attributes}
				setAttributes={setAttributes}
			/>
		</>
	);
}
```

That's it! Users can now copy and paste styles.

## API Reference

### `createStyleClipboard(storageKey, attributeKeys)`

Factory function that creates a clipboard instance.

**Parameters:**

- `storageKey` (string) - Unique localStorage key. Format: `"ub_blockname_copied_style"`
- `attributeKeys` (array) - List of block attribute names to copy/paste

**Returns:** Object with methods:

- `copy(attributes)` - Copy styles from block. Returns boolean.
- `get()` - Get copied style or null
- `has()` - Check if style is available to paste
- `clear()` - Clear stored style

**Example:**

```javascript
import { createStyleClipboard } from "$Inc/components/GenericStyleControl";

const clipboard = createStyleClipboard("ub_image_copied_style", [
	"height",
	"width",
	"aspectRatio",
	"border",
	"borderRadius",
]);
```

### `GenericStyleControl` Component

React component that adds copy/paste UI to block settings menu.

**Props:**

- `attributes` (object) - Current block attributes
- `setAttributes` (function) - Function to update block attributes
- `clipboard` (object) - Clipboard instance from `createStyleClipboard()`
- `blockName` (string, optional) - Display name for notifications (default: "Style")

**Example:**

```javascript
import GenericStyleControl from "$Inc/components/GenericStyleControl";

<GenericStyleControl
	attributes={blockAttributes}
	setAttributes={setBlockAttributes}
	clipboard={myClipboard}
	blockName="Button"
/>;
```

## Features

✓ **Reusable** - Use for any block with minimal configuration
✓ **Persistent** - Saves to localStorage, survives page refresh
✓ **Performant** - In-memory caching for fast access
✓ **User-friendly** - Success notifications on copy/paste
✓ **Smart** - Paste button disabled when nothing copied
✓ **DRY** - No code duplication across blocks

## Notes

- Only attributes listed in `attributeKeys` are copied
- Storage keys must be unique per block type
- Component automatically disables paste when nothing copied
- Notifications use WordPress snackbar UI
- Use path alias `$Inc/components/GenericStyleControl` for cleaner imports
